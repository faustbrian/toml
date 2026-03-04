<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\Exception\FileNotFoundException;
use Cline\Toml\Exception\FileNotReadableException;
use Cline\Toml\Exception\ParseException;
use Cline\Toml\Toml;

describe('Toml', function (): void {
    test('parse must parse a string', function (): void {
        $array = Toml::parse('data = "question"');

        expect($array)->toBe([
            'data' => 'question',
        ]);
    });

    test('parse must return null when string empty', function (): void {
        $array = Toml::parse('');

        expect($array)->toBeNull();
    });

    test('parseFile must parse file', function (): void {
        $filename = __DIR__.'/fixtures/simple.toml';

        $array = Toml::parseFile($filename);

        expect($array)->toBe([
            'name' => 'Víctor',
        ]);
    });

    test('parse must return an object when argument resultAsObject is true', function (): void {
        $actual = Toml::parse('name = "Víctor"', true);
        $expected = new stdClass();
        $expected->name = 'Víctor';

        expect($actual)->toEqual($expected);
    });

    test('parseFile must return an object when argument resultAsObject is true', function (): void {
        $filename = __DIR__.'/fixtures/simple.toml';

        $actual = Toml::parseFile($filename, true);
        $expected = new stdClass();
        $expected->name = 'Víctor';

        expect($actual)->toEqual($expected);
    });

    test('parseFile must fail when filename does not exist', function (): void {
        $filename = __DIR__.'/fixtures/does-not-exists.toml';

        Toml::parseFile($filename);
    })->throws(FileNotFoundException::class);

    test('parse must throw ParseException with line number for syntax errors', function (): void {
        try {
            Toml::parse('invalid = ');
        } catch (ParseException $parseException) {
            expect($parseException->getParsedLine())->toBe(1);

            throw $parseException;
        }
    })->throws(ParseException::class);

    test('parseFile must throw ParseException with filename for syntax errors', function (): void {
        $filename = __DIR__.'/fixtures/invalid.toml';

        try {
            Toml::parseFile($filename);
        } catch (ParseException $parseException) {
            expect($parseException->getParsedFile())->toBe($filename);
            expect($parseException->getParsedLine())->toBe(1);

            throw $parseException;
        }
    })->throws(ParseException::class);

    test('FileNotReadableException has correct message', function (): void {
        $exception = FileNotReadableException::forFile('/path/to/file.toml');

        expect($exception)->toBeInstanceOf(FileNotReadableException::class);
        expect($exception->getMessage())->toBe('File "/path/to/file.toml" cannot be read.');
    });
});
