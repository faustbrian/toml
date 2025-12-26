<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use Cline\Toml\Exception\DuplicateArrayTableKeyException;
use Cline\Toml\Exception\DuplicateKeyException;
use Cline\Toml\Exception\DuplicateTableKeyException;
use Cline\Toml\Exception\EmptyKeyException;
use Cline\Toml\Exception\InvalidStringCharactersException;
use Cline\Toml\Exception\KeyDefinedAsImplicitTableException;
use Cline\Toml\Exception\MixedArrayTypesException;
use Cline\Toml\Exception\TableAlreadyDefinedAsArrayException;
use Cline\Toml\Exception\UnquotedKeyRequiredException;
use Cline\Toml\Exception\UnsupportedDataTypeException;
use DateTime;
use Deprecated;

use function array_keys;
use function array_values;
use function assert;
use function explode;
use function floor;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function mb_strpos;
use function mb_trim;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function str_replace;
use function str_starts_with;

/**
 * Programmatically generates TOML-formatted strings from PHP data.
 *
 * TomlBuilder provides a fluent interface for constructing TOML documents by
 * adding tables, key-value pairs, arrays of tables, and comments. It handles
 * type conversion, string escaping, key validation, and formatting according
 * to TOML specification, making it the inverse operation of the Parser.
 *
 * ```php
 * $toml = (new TomlBuilder())
 *     ->addTable('database')
 *     ->addValue('host', 'localhost')
 *     ->addValue('port', 5432)
 *     ->addArrayOfTable('servers')
 *     ->addValue('ip', '192.168.1.1')
 *     ->getTomlString();
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TomlBuilder
{
    /**
     * Cached array of special characters requiring escape sequences in basic strings.
     *
     * @var null|list<string>
     */
    private static ?array $specialCharacters = null;

    /**
     * Cached array of escaped representations for special characters.
     *
     * @var null|list<string>
     */
    private static ?array $escapedSpecialCharacters = null;

    /**
     * Maps special characters to their TOML escape sequences for basic strings.
     *
     * @var array<string, string>
     */
    private static array $specialCharactersMapping = [
        '\\' => '\\\\',
        '\\b' => '\\b',
        "\t" => '\\t',
        "\n" => '\\n',
        "\f" => '\\f',
        "\r" => '\\r',
        '"' => '\\"',
    ];

    /**
     * Indentation string prepended to nested content when formatting is enabled.
     */
    private string $prefix = '';

    /**
     * Accumulated TOML output built up through successive method calls.
     */
    private string $output = '';

    /**
     * Currently active key name used for error messages and validation.
     */
    private string $currentKey = '';

    /**
     * Current line number in the generated TOML output for formatting decisions.
     */
    private int $currentLine = 0;

    /**
     * Tracks defined keys and tables to prevent duplicates and enforce TOML hierarchy rules.
     */
    private readonly KeyStore $keyStore;

    /**
     * Create a new TOML builder instance.
     *
     * @param int $indent Number of spaces for indentation when formatting nested structures.
     *                    Set to 0 to disable indentation. Default is 4 spaces.
     */
    public function __construct(int $indent = 4)
    {
        $this->keyStore = new KeyStore();
        $this->prefix = $indent > 0 ? str_repeat(' ', $indent) : '';
    }

    /**
     * Add a key-value pair to the current table.
     *
     * Converts PHP values to TOML syntax with appropriate type handling. Strings
     * are escaped and quoted, numbers are formatted, booleans become "true"/"false",
     * DateTime objects use ISO 8601, and arrays are serialized as TOML arrays.
     *
     * @param string                                           $key     Key name following TOML key rules (alphanumeric, underscores, hyphens)
     * @param null|array<mixed>|bool|DateTime|float|int|string $val     Value to serialize.
     *                                                                  Null values throw an exception.
     * @param string                                           $comment Optional inline comment added after the value
     *
     * @throws DuplicateKeyException        If the key was already defined in current scope
     * @throws EmptyKeyException            If the key is empty or whitespace-only
     * @throws UnsupportedDataTypeException If the value type cannot be represented in TOML
     * @return $this                        Fluent interface for chaining method calls
     */
    public function addValue(string $key, mixed $val, string $comment = ''): self
    {
        if (!is_string($val) && !is_int($val) && !is_bool($val) && !is_float($val) && !is_array($val) && !$val instanceof DateTime) {
            throw UnsupportedDataTypeException::forKey($key);
        }

        $this->currentKey = $key;
        $this->exceptionIfKeyEmpty($key);
        $this->addKey($key);

        if (!$this->isUnquotedKey($key)) {
            $key = '"'.$key.'"';
        }

        $line = sprintf('%s = %s', $key, $this->dumpValue($val));

        if ($comment !== '') {
            $line .= ' '.$this->dumpComment($comment);
        }

        $this->append($line, true);

        return $this;
    }

    /**
     * Add a table header to the TOML output.
     *
     * Creates a [table] section. Subsequent addValue() calls add keys to this table
     * until another table or array-of-tables is declared. Supports dotted keys for
     * nested tables (e.g., "server.database" creates nested structure).
     *
     * @param string $key Table name using dotted notation for nesting (e.g., "server.mail").
     *                    Each segment must be a valid unquoted key (alphanumeric/underscore/hyphen).
     *
     * @throws DuplicateTableKeyException          If the table was already defined
     * @throws EmptyKeyException                   If the table name or any segment is empty
     * @throws TableAlreadyDefinedAsArrayException If the key was used as array-of-tables
     * @throws UnquotedKeyRequiredException        If any key segment contains invalid characters
     * @return $this                               Fluent interface for chaining method calls
     */
    public function addTable(string $key): self
    {
        $this->exceptionIfKeyEmpty($key);
        $addPreNewline = $this->currentLine > 0;
        $keyParts = explode('.', $key);

        foreach ($keyParts as $keyPart) {
            $this->exceptionIfKeyEmpty($keyPart, sprintf('Table: "%s".', $key));
            $this->exceptionIfKeyIsNotUnquotedKey($keyPart);
        }

        $line = sprintf('[%s]', $key);
        $this->addTableKey($key);
        $this->append($line, true, false, $addPreNewline);

        return $this;
    }

    /**
     * This method has been marked as deprecated and will be deleted in version 2.0.0.
     *
     * @return $this
     */
    #[Deprecated(message: 'Use the method "addArrayOfTable" instead', since: '2.0.0')]
    public function addArrayTables(string $key): self
    {
        return $this->addArrayOfTable($key);
    }

    /**
     * Add an array-of-tables element header to the TOML output.
     *
     * Creates a [[array]] section representing one element in an array of tables.
     * Each call to addArrayOfTable() with the same key adds another element.
     * Subsequent addValue() calls populate the current array element.
     *
     * @param string $key Array-of-tables name using dotted notation if nested.
     *                    Each segment must be a valid unquoted key.
     *
     * @throws DuplicateArrayTableKeyException    If attempting to redefine as non-array table
     * @throws EmptyKeyException                  If the key name or any segment is empty
     * @throws KeyDefinedAsImplicitTableException If key conflicts with implicit table hierarchy
     * @throws UnquotedKeyRequiredException       If any key segment contains invalid characters
     * @return $this                              Fluent interface for chaining method calls
     */
    public function addArrayOfTable(string $key): self
    {
        $this->exceptionIfKeyEmpty($key);
        $addPreNewline = $this->currentLine > 0;
        $keyParts = explode('.', $key);

        foreach ($keyParts as $keyPart) {
            $this->exceptionIfKeyEmpty($keyPart, sprintf('Array of table: "%s".', $key));
            $this->exceptionIfKeyIsNotUnquotedKey($keyPart);
        }

        $line = sprintf('[[%s]]', $key);
        $this->addArrayOfTableKey($key);
        $this->append($line, true, false, $addPreNewline);

        return $this;
    }

    /**
     * Add a standalone comment line to the TOML output.
     *
     * Creates a line beginning with "#" for documentation purposes. Comments do not
     * affect parsing and can appear anywhere in the document.
     *
     * @param string $comment Comment text without the leading "#" (automatically prepended)
     *
     * @return $this Fluent interface for chaining method calls
     */
    public function addComment(string $comment): self
    {
        $this->append($this->dumpComment($comment), true);

        return $this;
    }

    /**
     * Retrieve the complete TOML document as a string.
     *
     * Returns the accumulated TOML output from all addValue(), addTable(),
     * addArrayOfTable(), and addComment() calls. The resulting string can be
     * written to files or parsed back using Toml::parse().
     *
     * @return string Complete TOML document ready for output or storage
     */
    public function getTomlString(): string
    {
        return $this->output;
    }

    /**
     * Get the list of escaped character sequences for string validation.
     *
     * Returns cached array of escape sequences like "\\n", "\\t", "\\"" used
     * in TOML basic strings. Lazily initialized on first call.
     *
     * @phpstan-return list<string>
     */
    private function getEscapedCharacters(): array
    {
        if (self::$escapedSpecialCharacters !== null) {
            return self::$escapedSpecialCharacters;
        }

        /** @var list<string> $result */
        $result = array_values(self::$specialCharactersMapping);

        return self::$escapedSpecialCharacters = $result;
    }

    /**
     * Get the list of special characters requiring escaping in basic strings.
     *
     * Returns cached array of characters that must be escaped in TOML basic strings
     * including backslash, quotes, and control characters. Lazily initialized.
     *
     * @return list<string> Array of raw characters needing escaping (e.g., ["\\", "\t", "\n"])
     */
    private function getSpecialCharacters(): array
    {
        if (self::$specialCharacters !== null) {
            return self::$specialCharacters;
        }

        return self::$specialCharacters = array_keys(self::$specialCharactersMapping);
    }

    /**
     * Register a key in the key store to prevent duplicate definitions.
     *
     * Validates that the key hasn't been defined previously in the current scope,
     * then adds it to the registry. This enforces TOML's constraint that keys
     * cannot be redefined within the same table.
     *
     * @param string $key Key name to register
     *
     * @throws DuplicateKeyException If the key was already defined in this scope
     */
    private function addKey(string $key): void
    {
        if (!$this->keyStore->isValidKey($key)) {
            throw DuplicateKeyException::forKey($key);
        }

        $this->keyStore->addKey($key);
    }

    /**
     * Register a table key in the key store to prevent duplicate table definitions.
     *
     * Validates that the table name hasn't been used and doesn't conflict with
     * array-of-tables definitions, then adds it to the registry.
     *
     * @param string $key Fully qualified table name
     *
     * @throws DuplicateTableKeyException          If the table was already defined
     * @throws TableAlreadyDefinedAsArrayException If the key was used as array-of-tables
     */
    private function addTableKey(string $key): void
    {
        if (!$this->keyStore->isValidTableKey($key)) {
            throw DuplicateTableKeyException::forKey($key);
        }

        if ($this->keyStore->isRegisteredAsArrayTableKey($key)) {
            throw TableAlreadyDefinedAsArrayException::forKey($key);
        }

        $this->keyStore->addTableKey($key);
    }

    /**
     * Register an array-of-tables key in the key store.
     *
     * Validates that the array-of-tables name is valid and doesn't conflict with
     * implicit table hierarchies, then adds it to the registry.
     *
     * @param string $key Fully qualified array-of-tables name
     *
     * @throws DuplicateArrayTableKeyException    If attempting to redefine as regular table
     * @throws KeyDefinedAsImplicitTableException If key conflicts with implicit hierarchy
     */
    private function addArrayOfTableKey(string $key): void
    {
        if (!$this->keyStore->isValidArrayTableKey($key)) {
            throw DuplicateArrayTableKeyException::forKey($key);
        }

        if ($this->keyStore->isTableImplicitFromArryTable($key)) {
            throw KeyDefinedAsImplicitTableException::forKey($key);
        }

        $this->keyStore->addArrayTableKey($key);
    }

    /**
     * Convert a PHP value to its TOML string representation.
     *
     * Dispatches to type-specific serialization methods based on the value's type.
     * Strings are quoted and escaped, numbers are formatted, booleans become
     * "true"/"false", arrays are bracketed, and DateTime uses ISO 8601.
     *
     * @param array<mixed>|bool|DateTime|float|int|string $val Value to serialize to TOML syntax
     *
     * @throws UnsupportedDataTypeException If the value type cannot be represented in TOML
     * @return string                       TOML-formatted representation of the value
     */
    private function dumpValue(string|int|bool|float|array|DateTime $val): string
    {
        return match (true) {
            is_string($val) => $this->dumpString($val),
            is_array($val) => $this->dumpArray($val),
            is_int($val) => $this->dumpInteger($val),
            is_float($val) => $this->dumpFloat($val),
            is_bool($val) => $this->dumpBool($val),
            default => $this->dumpDatetime($val),
        };
    }

    /**
     * Append content to the output with optional formatting.
     *
     * Manages output generation with control over newlines and indentation.
     * Tracks line numbers for formatting decisions like adding blank lines
     * before table headers.
     *
     * @param string $val            Content to append to the output
     * @param bool   $addPostNewline Add newline after the content (default false)
     * @param bool   $addIndentation Prepend indentation prefix to the content (default false)
     * @param bool   $addPreNewline  Add newline before the content (default false)
     */
    private function append(string $val, bool $addPostNewline = false, bool $addIndentation = false, bool $addPreNewline = false): void
    {
        if ($addPreNewline) {
            $this->output .= "\n";
            ++$this->currentLine;
        }

        if ($addIndentation) {
            $val = $this->prefix.$val;
        }

        $this->output .= $val;

        if (!$addPostNewline) {
            return;
        }

        $this->output .= "\n";
        ++$this->currentLine;
    }

    private function dumpString(string $val): string
    {
        if ($this->isLiteralString($val)) {
            return "'".preg_replace('/@/', '', $val, 1)."'";
        }

        $normalized = $this->normalizeString($val);

        if (!$this->isStringValid($normalized)) {
            throw InvalidStringCharactersException::forKey($this->currentKey);
        }

        return '"'.$normalized.'"';
    }

    private function isLiteralString(string $val): bool
    {
        return str_starts_with($val, '@');
    }

    private function dumpBool(bool $val): string
    {
        return $val ? 'true' : 'false';
    }

    /**
     * @param array<mixed> $val
     */
    private function dumpArray(array $val): string
    {
        $result = '';
        $first = true;
        $dataType = null;
        $lastType = null;

        foreach ($val as $item) {
            $lastType = gettype($item);
            $dataType ??= $lastType;

            if ($lastType !== $dataType) {
                throw MixedArrayTypesException::forKey($this->currentKey);
            }

            // At this point $item must be a valid TOML type
            assert(is_string($item) || is_int($item) || is_bool($item) || is_float($item) || is_array($item) || $item instanceof DateTime);
            $result .= $first ? $this->dumpValue($item) : ', '.$this->dumpValue($item);
            $first = false;
        }

        return '['.$result.']';
    }

    private function dumpComment(string $val): string
    {
        return '#'.$val;
    }

    private function dumpDatetime(DateTime $val): string
    {
        return $val->format('Y-m-d\TH:i:s\Z'); // ZULU form
    }

    private function dumpInteger(int $val): string
    {
        return (string) $val;
    }

    private function dumpFloat(float $val): string
    {
        $result = (string) $val;

        if ($val === floor($val)) {
            $result .= '.0';
        }

        return $result;
    }

    private function isStringValid(string $val): bool
    {
        /** @var list<string> $escapedChars */
        $escapedChars = $this->getEscapedCharacters();
        $noSpecialCharacter = str_replace($escapedChars, '', $val);
        $noSpecialCharacter = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '', $noSpecialCharacter);
        $noSpecialCharacter = preg_replace('/\\\\u([0-9a-fA-F]{8})/', '', (string) $noSpecialCharacter);

        $pos = mb_strpos((string) $noSpecialCharacter, '\\');

        return $pos === false;
    }

    private function normalizeString(string $val): string
    {
        /** @var list<string> $specialChars */
        $specialChars = $this->getSpecialCharacters();

        /** @var list<string> $escapedChars */
        $escapedChars = $this->getEscapedCharacters();

        return str_replace($specialChars, $escapedChars, $val);
    }

    private function exceptionIfKeyEmpty(string $key, string $additionalMessage = ''): void
    {
        if (mb_trim($key) === '') {
            throw EmptyKeyException::create($additionalMessage);
        }
    }

    private function exceptionIfKeyIsNotUnquotedKey(string $key): void
    {
        if (!$this->isUnquotedKey($key)) {
            throw UnquotedKeyRequiredException::forKey($key);
        }
    }

    private function isUnquotedKey(string $key): bool
    {
        return preg_match('/^([-A-Z_a-z0-9]+)$/', $key) === 1;
    }
}
