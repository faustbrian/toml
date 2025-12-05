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

use Cline\Toml\Lexer;

beforeEach(function (): void {
    $this->lexer = new Lexer();
});

test('tokenize recognizes equal token', function (): void {
    $ts = $this->lexer->tokenize('=');

    expect($ts->isNext('T_EQUAL'))->toBeTrue();
});

test('tokenize recognizes boolean token when there is a true value', function (): void {
    $ts = $this->lexer->tokenize('true');

    expect($ts->isNext('T_BOOLEAN'))->toBeTrue();
});

test('tokenize recognizes boolean token when there is a false value', function (): void {
    $ts = $this->lexer->tokenize('false');

    expect($ts->isNext('T_BOOLEAN'))->toBeTrue();
});

test('tokenize recognizes unquoted key token', function (): void {
    $ts = $this->lexer->tokenize('title');

    expect($ts->isNext('T_UNQUOTED_KEY'))->toBeTrue();
});

test('tokenize recognizes integer token when there is a positive number', function (): void {
    $ts = $this->lexer->tokenize('25');

    expect($ts->isNext('T_INTEGER'))->toBeTrue();
});

test('tokenize recognizes integer token when there is a negative number', function (): void {
    $ts = $this->lexer->tokenize('-25');

    expect($ts->isNext('T_INTEGER'))->toBeTrue();
});

test('tokenize recognizes integer token when there is number with underscore separator', function (): void {
    $ts = $this->lexer->tokenize('2_5');

    expect($ts->isNext('T_INTEGER'))->toBeTrue();
});

test('tokenize recognizes float token when there is a float number', function (): void {
    $ts = $this->lexer->tokenize('2.5');

    expect($ts->isNext('T_FLOAT'))->toBeTrue();
});

test('tokenize recognizes float token when there is a float number with underscore separator', function (): void {
    $ts = $this->lexer->tokenize('9_224_617.445_991_228_313');

    expect($ts->isNext('T_FLOAT'))->toBeTrue();
});

test('tokenize recognizes float token when there is a negative float number', function (): void {
    $ts = $this->lexer->tokenize('-2.5');

    expect($ts->isNext('T_FLOAT'))->toBeTrue();
});

test('tokenize recognizes float token when there is a number with exponent', function (): void {
    $ts = $this->lexer->tokenize('5e+22');

    expect($ts->isNext('T_FLOAT'))->toBeTrue();
});

test('tokenize recognizes float token when there is a number with exponent and underscore separator', function (): void {
    $ts = $this->lexer->tokenize('1e1_000');

    expect($ts->isNext('T_FLOAT'))->toBeTrue();
});

test('tokenize recognizes float token when there is a float number with exponent', function (): void {
    $ts = $this->lexer->tokenize('6.626e-34');

    expect($ts->isNext('T_FLOAT'))->toBeTrue();
});

test('tokenize recognizes datetime token when there is RFC3339 datetime', function (): void {
    $ts = $this->lexer->tokenize('1979-05-27T07:32:00Z');

    expect($ts->isNext('T_DATE_TIME'))->toBeTrue();
});

test('tokenize recognizes datetime token when there is RFC3339 datetime with offset', function (): void {
    $ts = $this->lexer->tokenize('1979-05-27T00:32:00-07:00');

    expect($ts->isNext('T_DATE_TIME'))->toBeTrue();
});

test('tokenize recognizes datetime token when there is RFC3339 datetime with offset second fraction', function (): void {
    $ts = $this->lexer->tokenize('1979-05-27T00:32:00.999999-07:00');

    expect($ts->isNext('T_DATE_TIME'))->toBeTrue();
});

test('tokenize recognizes quotation mark', function (): void {
    $ts = $this->lexer->tokenize('"');

    expect($ts->isNext('T_QUOTATION_MARK'))->toBeTrue();
});

test('tokenize recognizes 3 quotation marks', function (): void {
    $ts = $this->lexer->tokenize('"""');

    expect($ts->isNextSequence(['T_3_QUOTATION_MARK', 'T_EOS']))->toBeTrue();
});

test('tokenize recognizes apostrophe', function (): void {
    $ts = $this->lexer->tokenize("'");

    expect($ts->isNext('T_APOSTROPHE'))->toBeTrue();
});

test('tokenize recognizes 3 apostrophes', function (): void {
    $ts = $this->lexer->tokenize("'''");

    expect($ts->isNext('T_3_APOSTROPHE'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is backspace', function (): void {
    $ts = $this->lexer->tokenize('\b');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is tab', function (): void {
    $ts = $this->lexer->tokenize('\t');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is linefeed', function (): void {
    $ts = $this->lexer->tokenize('\n');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is formfeed', function (): void {
    $ts = $this->lexer->tokenize('\f');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is carriage return', function (): void {
    $ts = $this->lexer->tokenize('\r');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is quote', function (): void {
    $ts = $this->lexer->tokenize('\"');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is backslash', function (): void {
    $ts = $this->lexer->tokenize('\\\\');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is unicode using four characters', function (): void {
    $ts = $this->lexer->tokenize('\u00E9');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes escaped character when there is unicode using eight characters', function (): void {
    $ts = $this->lexer->tokenize('\U00E90000');

    expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
});

test('tokenize recognizes basic unescaped string', function (): void {
    $ts = $this->lexer->tokenize('@text');

    expect($ts->isNextSequence(['T_BASIC_UNESCAPED', 'T_EOS']))->toBeTrue();
});

test('tokenize recognizes hash', function (): void {
    $ts = $this->lexer->tokenize('#');

    expect($ts->isNext('T_HASH'))->toBeTrue();
});

test('tokenize recognizes escape', function (): void {
    $ts = $this->lexer->tokenize('\\');

    expect($ts->isNextSequence(['T_ESCAPE', 'T_EOS']))->toBeTrue();
});

test('tokenize recognizes escape and escaped character', function (): void {
    $ts = $this->lexer->tokenize('\\ \b');

    expect($ts->isNextSequence(['T_ESCAPE', 'T_SPACE', 'T_ESCAPED_CHARACTER', 'T_EOS']))->toBeTrue();
});

test('tokenize recognizes left square bracket', function (): void {
    $ts = $this->lexer->tokenize('[');

    expect($ts->isNext('T_LEFT_SQUARE_BRAKET'))->toBeTrue();
});

test('tokenize recognizes right square bracket', function (): void {
    $ts = $this->lexer->tokenize(']');

    expect($ts->isNext('T_RIGHT_SQUARE_BRAKET'))->toBeTrue();
});

test('tokenize recognizes dot', function (): void {
    $ts = $this->lexer->tokenize('.');

    expect($ts->isNext('T_DOT'))->toBeTrue();
});

test('tokenize recognizes left curly brace', function (): void {
    $ts = $this->lexer->tokenize('{');

    expect($ts->isNext('T_LEFT_CURLY_BRACE'))->toBeTrue();
});

test('tokenize recognizes right curly brace', function (): void {
    $ts = $this->lexer->tokenize('}');

    expect($ts->isNext('T_RIGHT_CURLY_BRACE'))->toBeTrue();
});
