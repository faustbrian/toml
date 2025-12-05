<?php

declare(strict_types=1);

/*
 * This file is part of the Cline Toml package.
 *
 * (c) Cline <https://cline.sh>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\Toml;
use Cline\Toml\TomlBuilder;

test('example builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addComment('Toml file')
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
            ->addValue('datetime', new DateTime())
        ->addComment('Related to arrays')
        ->addTable('data.array')
            ->addValue('simple', [1, 2, 3])
            ->addValue('multiple', [[1, 2], ['abc', 'def'], [1.1, 1.2], [true, false], [new DateTime()]])
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('array empty builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addComment('Toml file')
        ->addValue('thevoid', [])
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('implicit and explicit after builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addTable('a.b.c')
            ->addValue('answer', 42)
        ->addTable('a')
            ->addValue('better', 43)
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('implicit and explicit before builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addTable('a')
            ->addValue('better', 43)
        ->addTable('a.b.c')
            ->addValue('answer', 42)
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('table empty builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addTable('a')
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('table sub empty builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addTable('a')
        ->addTable('a.b')
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('key whitespace builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addValue('valid key', 2)
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('string escapes double quote builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addValue('backspace', "This string has a \b backspace character.")
        ->addValue('tab', "This string has a \t tab character.")
        ->addValue('newline', "This string has a \n new line character.")
        ->addValue('formfeed', "This string has a \f form feed character.")
        ->addValue('carriage', "This string has a \r carriage return character.")
        ->addValue('quote', 'This string has a " quote character.')
        ->addValue('slash', "This string has a / slash character.")
        ->addValue('backslash', 'This string has a \\ backslash character.')
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('key literal string builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addValue('regex', "@<\i\c*\s*>")
        ->getTomlString();

    $array = Toml::Parse($result);

    expect($array)->not->toBeNull();
    expect($array['regex'])->toBe('<\i\c*\s*>');
});

test('key literal string escaping at builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addValue('regex', "@@<\i\c*\s*>")
        ->getTomlString();

    $array = Toml::Parse($result);

    expect($array)->not->toBeNull();
    expect($array['regex'])->toBe('@<\i\c*\s*>');
});

test('key special chars builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addValue('~!@$^&*()_+-`1234567890[]|/?><.,;:\'', 1)
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('string escapes single quote builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addValue('backspace', 'This string has a \b backspace character.')
        ->addValue('tab', 'This string has a \t tab character.')
        ->addValue('newline', 'This string has a \n new line character.')
        ->addValue('formfeed', 'This string has a \f form feed character.')
        ->addValue('carriage', 'This string has a \r carriage return character.')
        ->addValue('quote', 'This string has a \" quote character.')
        ->addValue('slash', 'This string has a \/ slash character.')
        ->addValue('backslash', 'This string has a \\ backslash character.')
        ->getTomlString();

    expect(Toml::Parse($result))->not->toBeNull();
});

test('array of tables builds valid TOML', function (): void {
    $tb = new TomlBuilder();

    $result = $tb->addArrayOfTable('fruit')
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

    expect(Toml::Parse($result))->not->toBeNull();
});
