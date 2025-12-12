<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\Toml;
use Illuminate\Support\Facades\Date;

describe('TomlBuilder', function (): void {
    test('example', function (): void {
        $result = createTomlBuilder()
            ->addComment('Toml file')
            ->addTable('data.string')
            ->addValue('name', 'Toml', 'This is your name')
            ->addValue('newline', "This string has a \n new line character.")
            ->addValue('winPath', 'C:\\Users\\nodejs\\templates')
            ->addValue('unicode', 'unicode character: '.json_decode('"\u03B4"'))
            ->addTable('data.bool')
            ->addValue('t', true)
            ->addValue('f', false)
            ->addTable('data.integer')
            ->addValue('positive', 25, 'Comment inline.')
            ->addValue('negative', -25)
            ->addTable('data.float')
            ->addValue('positive', 25.25)
            ->addValue('negative', -25.25)
            ->addTable('data.datetime')
            ->addValue('datetime', Date::now())
            ->addComment('Related to arrays')
            ->addTable('data.array')
            ->addValue('simple', [1, 2, 3])
            ->addValue('multiple', [[1, 2], ['abc', 'def'], [1.1, 1.2], [true, false], [Date::now()]])
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('array empty', function (): void {
        $result = createTomlBuilder()
            ->addComment('Toml file')
            ->addValue('thevoid', [])
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('implicit and explicit after', function (): void {
        $result = createTomlBuilder()
            ->addTable('a.b.c')
            ->addValue('answer', 42)
            ->addTable('a')
            ->addValue('better', 43)
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('implicit and explicit before', function (): void {
        $result = createTomlBuilder()
            ->addTable('a')
            ->addValue('better', 43)
            ->addTable('a.b.c')
            ->addValue('answer', 42)
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('table empty', function (): void {
        $result = createTomlBuilder()
            ->addTable('a')
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('table sub empty', function (): void {
        $result = createTomlBuilder()
            ->addTable('a')
            ->addTable('a.b')
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('key whitespace', function (): void {
        $result = createTomlBuilder()
            ->addValue('valid key', 2)
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('string escapes double quote', function (): void {
        $result = createTomlBuilder()
            ->addValue('backspace', "This string has a \x08 backspace character.")
            ->addValue('tab', "This string has a \t tab character.")
            ->addValue('newline', "This string has a \n new line character.")
            ->addValue('formfeed', "This string has a \f form feed character.")
            ->addValue('carriage', "This string has a \r carriage return character.")
            ->addValue('quote', 'This string has a " quote character.')
            ->addValue('slash', 'This string has a / slash character.')
            ->addValue('backslash', 'This string has a \\ backslash character.')
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('key literal string', function (): void {
        $result = createTomlBuilder()
            ->addValue('regex', '@<\\i\\c*\\s*>')
            ->getTomlString();

        $array = Toml::parse($result);

        expect($array)->not->toBeNull();
        expect($array['regex'])->toBe('<\\i\\c*\\s*>');
    });

    test('key literal string escaping at', function (): void {
        $result = createTomlBuilder()
            ->addValue('regex', '@@<\\i\\c*\\s*>')
            ->getTomlString();

        $array = Toml::parse($result);

        expect($array)->not->toBeNull();
        expect($array['regex'])->toBe('@<\\i\\c*\\s*>');
    });

    test('key special chars', function (): void {
        $result = createTomlBuilder()
            ->addValue('~!@$^&*()_+-`1234567890[]|/?><.,;:\'', 1)
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('string escapes single quote', function (): void {
        $result = createTomlBuilder()
            ->addValue('backspace', 'This string has a \b backspace character.')
            ->addValue('tab', 'This string has a \t tab character.')
            ->addValue('newline', 'This string has a \n new line character.')
            ->addValue('formfeed', 'This string has a \f form feed character.')
            ->addValue('carriage', 'This string has a \r carriage return character.')
            ->addValue('quote', 'This string has a \" quote character.')
            ->addValue('slash', 'This string has a \/ slash character.')
            ->addValue('backslash', 'This string has a \\\\ backslash character.')
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });

    test('array of tables', function (): void {
        $result = createTomlBuilder()
            ->addArrayOfTable('fruit')
            ->addValue('name', 'apple')
            ->addArrayOfTable('fruit.variety')
            ->addValue('name', 'red delicious')
            ->addArrayOfTable('fruit.variety')
            ->addValue('name', 'granny smith')
            ->addArrayOfTable('fruit')
            ->addValue('name', 'banana')
            ->addArrayOfTable('fruit.variety')
            ->addValue('name', 'plantain')
            ->getTomlString();

        expect(Toml::parse($result))->not->toBeNull();
    });
});
