<?php

declare(strict_types=1);

/*
 * This file is part of the Cline\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use Cline\Toml\Exception\DuplicateArrayOfTableKeyException;
use Cline\Toml\Exception\DuplicateKeyException;
use Cline\Toml\Exception\DuplicateTableKeyException;
use Cline\Toml\Exception\EmptyKeyException;
use Cline\Toml\Exception\ImplicitTableFromArrayOfTablesException;
use Cline\Toml\Exception\InvalidStringCharacterException;
use Cline\Toml\Exception\MixedArrayTypesException;
use Cline\Toml\Exception\TableDefinedAsArrayOfTablesException;
use Cline\Toml\Exception\UnquotedKeyRequiredException;
use Cline\Toml\Exception\UnsupportedArrayTypeException;

/**
 * Create inline TOML strings.
 *
 * @author Victor Puertas <vpgugr@gmail.com>
 *
 * Usage:
 * <code>
 * $tomlString = new TomlBuilder()
 *  ->addTable('server.mail')
 *  ->addValue('ip', '192.168.0.1', 'Internal IP')
 *  ->addValue('port', 25)
 *  ->getTomlString();
 * </code>
 */
class TomlBuilder
{
    protected string $prefix = '';

    protected string $output = '';

    protected string $currentKey = '';

    protected KeyStore $keyStore;

    private int $currentLine = 0;

    /** @var list<string>|null */
    private static ?array $specialCharacters = null;

    /** @var list<string>|null */
    private static ?array $escapedSpecialCharacters = null;

    /** @var array<string, string> */
    private static array $specialCharactersMapping = [
        '\\' => '\\\\',
        "\b" => '\\b',
        "\t" => '\\t',
        "\n" => '\\n',
        "\f" => '\\f',
        "\r" => '\\r',
        '"' => '\\"',
    ];

    /**
     * Constructor.
     *
     * @param int $indent The amount of spaces to use for indentation of nested nodes
     */
    public function __construct(int $indent = 4)
    {
        $this->keyStore = new KeyStore;
        $this->prefix = $indent ? str_repeat(' ', $indent) : '';
    }

    /**
     * Adds a key value pair
     *
     * @param string                                       $key     The key name
     * @param string|int|bool|float|array<mixed>|\DateTime $val     The value
     * @param string                                       $comment Comment (optional argument).
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addValue(string $key, string|int|bool|float|array|\DateTime $val, string $comment = ''): TomlBuilder
    {
        $this->currentKey = $key;
        $this->exceptionIfKeyEmpty($key);
        $this->addKey($key);

        if (! $this->isUnquotedKey($key)) {
            $key = '"' . $key . '"';
        }

        $line = "{$key} = {$this->dumpValue($val)}";

        if ($comment !== '') {
            $line .= ' ' . $this->dumpComment($comment);
        }

        $this->append($line, true);

        return $this;
    }

    /**
     * Adds a table.
     *
     * @param string $key Table name. Dot character have a special mean. e.g: "fruit.type"
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addTable(string $key): TomlBuilder
    {
        $this->exceptionIfKeyEmpty($key);
        $addPreNewline = $this->currentLine > 0;
        $keyParts = explode('.', $key);

        foreach ($keyParts as $keyPart) {
            $this->exceptionIfKeyEmpty($keyPart, "Table: \"{$key}\".");
            $this->exceptionIfKeyIsNotUnquotedKey($keyPart);
        }

        $line = "[{$key}]";
        $this->addTableKey($key);
        $this->append($line, true, false, $addPreNewline);

        return $this;
    }

    /**
     * This method has been marked as deprecated and will be deleted in version 2.0.0
     *
     * @deprecated 2.0.0 Use the method "addArrayOfTable" instead
     */
    public function addArrayTables(string $key): TomlBuilder
    {
        return $this->addArrayOfTable($key);
    }

    /**
     * Adds an array of tables element
     *
     * @param string $key The name of the array of tables
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addArrayOfTable(string $key): TomlBuilder
    {
        $this->exceptionIfKeyEmpty($key);
        $addPreNewline = $this->currentLine > 0;
        $keyParts = explode('.', $key);

        foreach ($keyParts as $keyPart) {
            $this->exceptionIfKeyEmpty($keyPart, "Array of table: \"{$key}\".");
            $this->exceptionIfKeyIsNotUnquotedKey($keyPart);
        }

        $line = "[[{$key}]]";
        $this->addArrayOfTableKey($key);
        $this->append($line, true, false, $addPreNewline);

        return $this;
    }

    /**
     * Adds a comment line
     *
     * @param string $comment The comment
     *
     * @return TomlBuilder The TomlBuilder itself
     */
    public function addComment(string $comment): TomlBuilder
    {
        $this->append($this->dumpComment($comment), true);

        return $this;
    }

    /**
     * Gets the TOML string
     */
    public function getTomlString(): string
    {
        return $this->output;
    }

    /**
     * Returns the escaped characters for basic strings
     *
     * @return list<string>
     */
    protected function getEscapedCharacters(): array
    {
        if (self::$escapedSpecialCharacters !== null) {
            return self::$escapedSpecialCharacters;
        }

        return self::$escapedSpecialCharacters = \array_values(self::$specialCharactersMapping);
    }

    /**
     * Returns the special characters for basic strings
     *
     * @return list<string>
     */
    protected function getSpecialCharacters(): array
    {
        if (self::$specialCharacters !== null) {
            return self::$specialCharacters;
        }

        return self::$specialCharacters = \array_keys(self::$specialCharactersMapping);
    }

    /**
     * Adds a key to the store
     *
     * @param string $key The key name
     */
    protected function addKey(string $key): void
    {
        if (! $this->keyStore->isValidKey($key)) {
            throw DuplicateKeyException::forKey($key);
        }

        $this->keyStore->addKey($key);
    }

    /**
     * Adds a table key to the store
     *
     * @param string $key The table key name
     */
    protected function addTableKey(string $key): void
    {
        if (! $this->keyStore->isValidTableKey($key)) {
            throw DuplicateTableKeyException::forKey($key);
        }

        if ($this->keyStore->isRegisteredAsArrayTableKey($key)) {
            throw TableDefinedAsArrayOfTablesException::forKey($key);
        }

        $this->keyStore->addTableKey($key);
    }

    /**
     * Adds an array of table key to the store
     *
     * @param string $key The key name
     */
    protected function addArrayOfTableKey(string $key): void
    {
        if (! $this->keyStore->isValidArrayTableKey($key)) {
            throw DuplicateArrayOfTableKeyException::forKey($key);
        }

        if ($this->keyStore->isTableImplicitFromArryTable($key)) {
            throw ImplicitTableFromArrayOfTablesException::forKey($key);
        }

        $this->keyStore->addArrayTableKey($key);
    }

    /**
     * Dumps a value
     *
     * @param string|int|bool|float|array<mixed>|\DateTime $val The value
     */
    protected function dumpValue(string|int|bool|float|array|\DateTime $val): string
    {
        return match (true) {
            is_string($val) => $this->dumpString($val),
            is_array($val) => $this->dumpArray($val),
            is_int($val) => $this->dumpInteger($val),
            is_float($val) => $this->dumpFloat($val),
            is_bool($val) => $this->dumpBool($val),
            $val instanceof \DateTime => $this->dumpDatetime($val),
        };
    }

    /**
     * Adds content to the output
     *
     * @param string $val            The value to append
     * @param bool   $addPostNewline Indicates if add a newline after the value
     * @param bool   $addIndentation Indicates if add indentation to the line
     * @param bool   $addPreNewline  Indicates if add a new line before the value
     */
    protected function append(string $val, bool $addPostNewline = false, bool $addIndentation = false, bool $addPreNewline = false): void
    {
        if ($addPreNewline) {
            $this->output .= "\n";
            ++$this->currentLine;
        }

        if ($addIndentation) {
            $val = $this->prefix . $val;
        }

        $this->output .= $val;

        if ($addPostNewline) {
            $this->output .= "\n";
            ++$this->currentLine;
        }
    }

    private function dumpString(string $val): string
    {
        if ($this->isLiteralString($val)) {
            return "'" . preg_replace('/@/', '', $val, 1) . "'";
        }

        $normalized = $this->normalizeString($val);

        if (! $this->isStringValid($normalized)) {
            throw InvalidStringCharacterException::forKey($this->currentKey);
        }

        return '"' . $normalized . '"';
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
            $dataType = $dataType === null ? $lastType : $dataType;

            if ($lastType !== $dataType) {
                throw MixedArrayTypesException::forKey($this->currentKey);
            }

            if (! is_string($item) && ! is_int($item) && ! is_bool($item) && ! is_float($item) && ! is_array($item) && ! $item instanceof \DateTime) {
                throw UnsupportedArrayTypeException::forKey($this->currentKey);
            }

            /** @var string|int|bool|float|array<mixed>|\DateTime $item */
            $result .= $first ? $this->dumpValue($item) : ', ' . $this->dumpValue($item);
            $first = false;
        }

        return '[' . $result . ']';
    }

    private function dumpComment(string $val): string
    {
        return '#' . $val;
    }

    private function dumpDatetime(\DateTime $val): string
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

        if ($val == floor($val)) {
            $result .= '.0';
        }

        return $result;
    }

    private function isStringValid(string $val): bool
    {
        $noSpecialCharacter = \str_replace($this->getEscapedCharacters(), '', $val);
        $noSpecialCharacter = \preg_replace('/\\\\u([0-9a-fA-F]{4})/', '', $noSpecialCharacter);
        $noSpecialCharacter = \preg_replace('/\\\\u([0-9a-fA-F]{8})/', '', (string) $noSpecialCharacter);

        if ($noSpecialCharacter === null) {
            return false;
        }

        $pos = strpos($noSpecialCharacter, '\\');

        if ($pos !== false) {
            return false;
        }

        return true;
    }

    private function normalizeString(string $val): string
    {
        return \str_replace($this->getSpecialCharacters(), $this->getEscapedCharacters(), $val);
    }

    private function exceptionIfKeyEmpty(string $key, string $additionalMessage = ''): void
    {
        if (trim($key) === '') {
            throw EmptyKeyException::create($additionalMessage);
        }
    }

    private function exceptionIfKeyIsNotUnquotedKey(string $key): void
    {
        if (! $this->isUnquotedKey($key)) {
            throw UnquotedKeyRequiredException::forKey($key);
        }
    }

    private function isUnquotedKey(string $key): bool
    {
        return \preg_match('/^([-A-Z_a-z0-9]+)$/', $key) === 1;
    }
}
