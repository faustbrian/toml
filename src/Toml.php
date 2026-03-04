<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use Cline\Toml\Exception\FileNotFoundException;
use Cline\Toml\Exception\FileNotReadableException;
use Cline\Toml\Exception\ParseException;
use Cline\Toml\Exception\SyntaxErrorException;
use Cline\Toml\Lexer\Token;
use stdClass;

use function assert;
use function file_get_contents;
use function is_file;
use function is_readable;
use function is_string;

/**
 * Convenient facade for parsing TOML format into PHP data structures.
 *
 * Provides static methods for parsing TOML from strings or files with automatic
 * error handling and format conversion. This is the primary entry point for most
 * applications using the TOML parser library.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Toml
{
    /**
     * Parse TOML string into a PHP array or object.
     *
     * Converts TOML-formatted text into native PHP data structures. Empty TOML
     * documents return null. Results can be returned as associative arrays
     * (default) or stdClass objects for property-style access.
     *
     * ```php
     * $config = Toml::parse('
     *     [database]
     *     host = "localhost"
     *     port = 5432
     * ');
     * echo $config['database']['host']; // "localhost"
     * ```
     *
     * @param string $input          Raw TOML content as a UTF-8 string with key-value pairs,
     *                               tables, and arrays following TOML specification syntax.
     * @param bool   $resultAsObject When true, returns stdClass instead of associative
     *                               array for top-level structure. Nested values remain
     *                               as arrays. Default is false.
     *
     * @throws ParseException                     If TOML syntax is invalid, with line number and error details
     * @return null|array<string, mixed>|stdClass Parsed structure as array/object, or null for empty documents
     */
    public static function parse(string $input, bool $resultAsObject = false): array|stdClass|null
    {
        try {
            $data = self::doParse($input, $resultAsObject);
        } catch (SyntaxErrorException $syntaxErrorException) {
            $exception = new ParseException($syntaxErrorException->getMessage(), -1, null, null, $syntaxErrorException);

            if (($token = $syntaxErrorException->getToken()) instanceof Token) {
                $exception->setParsedLine($token->getLine());
            }

            throw $exception;
        }

        return $data;
    }

    /**
     * Parse TOML file into a PHP array or object.
     *
     * Reads and parses a TOML file from the filesystem with automatic validation.
     * Validates file existence and readability before parsing. Line numbers in
     * parse errors refer to the line position within the file.
     *
     * ```php
     * $config = Toml::parseFile('/etc/app/config.toml');
     * $database = $config['database'] ?? [];
     * ```
     *
     * @param string $filename       Absolute or relative path to a TOML file. File must
     *                               exist and be readable by the current process.
     * @param bool   $resultAsObject When true, returns stdClass instead of associative
     *                               array for top-level structure. Default is false.
     *
     * @throws FileNotFoundException              If the specified file does not exist at the given path
     * @throws FileNotReadableException           If file exists but lacks read permissions
     * @throws ParseException                     If TOML syntax is invalid, includes filename and line number
     * @return null|array<string, mixed>|stdClass Parsed structure as array/object, or null for empty files
     */
    public static function parseFile(string $filename, bool $resultAsObject = false): array|stdClass|null
    {
        if (!is_file($filename)) {
            throw FileNotFoundException::forFile($filename);
        }

        if (!is_readable($filename)) {
            throw FileNotReadableException::forFile($filename);
        }

        try {
            $contents = file_get_contents($filename);
            // At this point file_get_contents cannot return false as we've checked is_readable
            assert(is_string($contents));
            $data = self::doParse($contents, $resultAsObject);
        } catch (SyntaxErrorException $syntaxErrorException) {
            $exception = new ParseException($syntaxErrorException->getMessage());
            $exception->setParsedFile($filename);

            if (($token = $syntaxErrorException->getToken()) instanceof Token) {
                $exception->setParsedLine($token->getLine());
            }

            throw $exception;
        }

        return $data;
    }

    /**
     * Internal parsing implementation shared by parse() and parseFile().
     *
     * Creates a Parser instance with a Lexer, processes the input, and optionally
     * converts the result to a stdClass object. Returns null for empty documents
     * instead of empty arrays to distinguish between explicit empty tables and
     * no content.
     *
     * @param string $input          TOML content to parse
     * @param bool   $resultAsObject Whether to return stdClass instead of array
     *
     * @return null|array<string, mixed>|stdClass Parsed TOML structure or null if empty
     */
    private static function doParse(string $input, bool $resultAsObject = false): array|stdClass|null
    {
        $parser = new Parser(
            new Lexer(),
        );
        $values = $parser->parse($input);

        if ($resultAsObject) {
            $object = new stdClass();

            foreach ($values as $key => $value) {
                $object->{$key} = $value;
            }

            return $object;
        }

        return $values === [] ? null : $values;
    }
}
