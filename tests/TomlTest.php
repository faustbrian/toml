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

use Cline\Toml\Exception\ParseException;
use Cline\Toml\Toml;

test('parse parses a string', function (): void {
    $array = Toml::parse('data = "question"');

    expect($array)->toBe([
        'data' => 'question',
    ]);
});

test('parse returns empty array when string empty', function (): void {
    $array = Toml::parse('');

    expect($array)->toBeNull();
});

test('parseFile parses file', function (): void {
    $filename = __DIR__.'/fixtures/simple.toml';

    $array = Toml::parseFile($filename);

    expect($array)->toBe([
        'name' => 'Víctor',
    ]);
});

test('parse returns an object when argument resultAsObject is true', function (): void {
    $actual = Toml::parse('name = "Víctor"', true);
    $expected = new stdClass();
    $expected->name = 'Víctor';

    expect($actual)->toEqual($expected);
});

test('parseFile returns an object when argument resultAsObject is true', function (): void {
    $filename = __DIR__.'/fixtures/simple.toml';

    $actual = Toml::parseFile($filename, true);
    $expected = new stdClass();
    $expected->name = 'Víctor';

    expect($actual)->toEqual($expected);
});

test('parseFile fails when filename does not exist', function (): void {
    $filename = __DIR__.'/fixtures/does-not-exists.toml';

    Toml::parseFile($filename);
})->throws(ParseException::class);
