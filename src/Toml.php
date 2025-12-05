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

use Cline\Toml\Exception\FileNotFoundException;
use Cline\Toml\Exception\FileNotReadableException;
use Cline\Toml\Exception\ParseException;
use Yosymfony\ParserUtils\SyntaxErrorException;

/**
 * Parser for TOML format.
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class Toml
{
    /**
     * Parses TOML into a PHP array.
     *
     * Usage:
     * <code>
     *  $array = Toml::parse('key = "[1,2,3]"');
     *  print_r($array);
     * </code>
     *
     * @param string $input          A string containing TOML
     * @param bool   $resultAsObject (optional) Returns the result as an object
     *
     * @return array<string, mixed>|\stdClass|null The TOML converted to a PHP value
     *
     * @throws ParseException If the TOML is not valid
     */
    public static function parse(string $input, bool $resultAsObject = false): array|\stdClass|null
    {
        try {
            $data = self::doParse($input, $resultAsObject);
        } catch (SyntaxErrorException $e) {
            $exception = new ParseException($e->getMessage(), -1, null, null, $e);

            if ($token = $e->getToken()) {
                $exception->setParsedLine($token->getLine());
            }

            throw $exception;
        }

        return $data;
    }

    /**
     * Parses a TOML file into a PHP array.
     *
     * Usage:
     * <code>
     *  $array = Toml::parseFile('config.toml');
     *  print_r($array);
     * </code>
     *
     * @param string $filename       A string containing the file path
     * @param bool   $resultAsObject (optional) Returns the result as an object
     *
     * @return array<string, mixed>|\stdClass|null The TOML converted to a PHP value
     *
     * @throws ParseException If the TOML file is not valid
     */
    public static function parseFile(string $filename, bool $resultAsObject = false): array|\stdClass|null
    {
        if (! is_file($filename)) {
            throw FileNotFoundException::forPath($filename);
        }

        if (! is_readable($filename)) {
            throw FileNotReadableException::forPath($filename);
        }

        try {
            $data = self::doParse((string) file_get_contents($filename), $resultAsObject);
        } catch (SyntaxErrorException $e) {
            $exception = new ParseException($e->getMessage());
            $exception->setParsedFile($filename);

            if ($token = $e->getToken()) {
                $exception->setParsedLine($token->getLine());
            }

            throw $exception;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|\stdClass|null
     */
    private static function doParse(string $input, bool $resultAsObject = false): array|\stdClass|null
    {
        $parser = new Parser(new Lexer);
        $values = $parser->parse($input);

        if ($resultAsObject) {
            $object = new \stdClass;

            foreach ($values as $key => $value) {
                $object->$key = $value;
            }

            return $object;
        }

        return $values === [] ? null : $values;
    }
}
