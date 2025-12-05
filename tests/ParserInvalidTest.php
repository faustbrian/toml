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

use Yosymfony\ParserUtils\SyntaxErrorException;

beforeEach(function (): void {
    $this->parser = createParser();
});

test('parse fails when key empty', function (): void {
    $this->parser->parse('= 1');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_EQUAL" at line 1 with value "=".');

test('parse fails when key hash', function (): void {
    $this->parser->parse('a# = 1');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_HASH" at line 1 with value "#".');

test('parse fails when key newline', function (): void {
    $this->parser->parse("a\n= 1");
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_NEWLINE" at line 1');

test('parse fails when duplicate keys', function (): void {
    $toml = <<<'toml'
        dupe = false
        dupe = true
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'The key "dupe" has already been defined previously.');

test('parse fails when key open bracket', function (): void {
    $this->parser->parse('[abc = 1');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_SPACE" at line 1');

test('parse fails when key single open bracket', function (): void {
    $this->parser->parse('[');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_EOS" at line 1');

test('parse fails when key space', function (): void {
    $this->parser->parse('a b = 1');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "b".');

test('parse fails when key start bracket', function (): void {
    $this->parser->parse("[a]\n[xyz = 5\n[b]");
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_SPACE" at line 2 with value " ".');

test('parse fails when key two equals', function (): void {
    $this->parser->parse('key= = 1');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_EQUAL" at line 1 with value "=".');

test('parse fails when text after integer', function (): void {
    $this->parser->parse('answer = 42 the ultimate answer?');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "the".');

test('parse fails when integer leading zeros', function (): void {
    $this->parser->parse('answer = 042');
})->throws(SyntaxErrorException::class, 'Invalid integer number: leading zeros are not allowed. Token: "T_INTEGER" line: 1 value "042".');

test('parse fails when integer leading underscore', function (): void {
    $this->parser->parse('answer = _42');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "_42".');

test('parse fails when integer final underscore', function (): void {
    $this->parser->parse('answer = 42_');
})->throws(SyntaxErrorException::class, 'Invalid integer number: underscore must be surrounded by at least one digit.');

test('parse fails when integer leading zeros with underscore', function (): void {
    $this->parser->parse('answer = 0_42');
})->throws(SyntaxErrorException::class, 'Invalid integer number: leading zeros are not allowed. Token: "T_INTEGER" line: 1 value "0_42".');

test('parse fails when float no leading zero', function (): void {
    $toml = <<<'toml'
        answer = .12345
        neganswer = -.12345
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_DOT" at line 1 with value ".".');

test('parse fails when float no trailing digits', function (): void {
    $toml = <<<'toml'
        answer = 1.
        neganswer = -1.
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_DOT" at line 1 with value ".".');

test('parse fails when float leading underscore', function (): void {
    $this->parser->parse('number = _1.01');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "_1".');

test('parse fails when float final underscore', function (): void {
    $this->parser->parse('number = 1.01_');
})->throws(SyntaxErrorException::class, 'Invalid float number: underscore must be surrounded by at least one digit.');

test('parse fails when float underscore prefix e', function (): void {
    $this->parser->parse('number = 1_e6');
})->throws(SyntaxErrorException::class, 'Invalid float number: underscore must be surrounded by at least one digit.');

test('parse fails when float underscore suffix e', function (): void {
    $this->parser->parse('number = 1e_6');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "e_6".');

test('parse fails when datetime malformed no leads', function (): void {
    $this->parser->parse('no-leads = 1987-7-05T17:45:00Z');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_INTEGER" at line 1 with value "-7".');

test('parse fails when datetime malformed no secs', function (): void {
    $this->parser->parse('no-secs = 1987-07-05T17:45Z');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "T17".');

test('parse fails when datetime malformed no t', function (): void {
    $this->parser->parse('no-t = 1987-07-0517:45:00Z');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_INTEGER" at line 1 with value "17".');

test('parse fails when datetime malformed with milli', function (): void {
    $this->parser->parse('with-milli = 1987-07-5T17:45:00.12Z');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_INTEGER" at line 1 with value "-07".');

test('parse fails when basic string has bad byte escape', function (): void {
    $this->parser->parse('naughty = "\xAg"');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_ESCAPE" at line 1 with value "\". This character is not valid.');

test('parse fails when basic string has bad escape', function (): void {
    $this->parser->parse('invalid-escape = "This string has a bad \a escape character."');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_ESCAPE" at line 1 with value "\". This character is not valid.');

test('parse fails when basic string has byte escapes', function (): void {
    $this->parser->parse('answer = "\x33"');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_ESCAPE" at line 1 with value "\". This character is not valid.');

test('parse fails when basic string is not closed', function (): void {
    $this->parser->parse('no-ending-quote = "One time, at band camp');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_EOS" at line 1 with value "". This character is not valid.');

test('parse fails when there is text after basic string', function (): void {
    $this->parser->parse('string = "Is there life after strings?" No.');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "No". Expected T_NEWLINE or T_EOS.');

test('parse fails when there is an array with mixed types arrays and ints', function (): void {
    $this->parser->parse('arrays-and-ints =  [1, ["Arrays are not integers."]]');
})->throws(SyntaxErrorException::class, 'Data types cannot be mixed in an array. Value: "array".');

test('parse fails when there is an array with mixed types ints and floats', function (): void {
    $this->parser->parse('ints-and-floats = [1, 1.1]');
})->throws(SyntaxErrorException::class, 'Data types cannot be mixed in an array. Value: "1.1".');

test('parse fails when there is an array with mixed types strings and ints', function (): void {
    $this->parser->parse('strings-and-ints = ["hi", 42]');
})->throws(SyntaxErrorException::class, 'Data types cannot be mixed in an array. Value: "42".');

test('parse fails when appears text after array entries', function (): void {
    $toml = <<<'toml'
        array = [
            "Is there life after an array separator?", No
            "Entry"
        ]
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 2 with value "No".');

test('parse fails when appears text before array separator', function (): void {
    $toml = <<<'toml'
        array = [
            "Is there life before an array separator?" No,
            "Entry"
        ]
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 2 with value "No".');

test('parse fails when appears text in array', function (): void {
    $toml = <<<'toml'
        array = [
            "Entry 1",
            I don't belong,
            "Entry 2",
        ]
toml;
    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 3 with value "I".');

test('parse fails when duplicate key table', function (): void {
    $toml = <<<'toml'
        [fruit]
        type = "apple"

        [fruit.type]
        apple = "yes"
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'The key "fruit.type" has already been defined previously.');

test('parse fails when duplicate table', function (): void {
    $toml = <<<'toml'
        [a]
        [a]
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'The key "a" has already been defined previously.');

test('parse fails when table empty', function (): void {
    $this->parser->parse('[]');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_RIGHT_SQUARE_BRAKET" at line 1 with value "]".');

test('parse fails when table whitespace', function (): void {
    $this->parser->parse('[invalid key]');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_SPACE" at line 1 with value " ".');

test('parse fails when empty implicit table', function (): void {
    $this->parser->parse('[naughty..naughty]');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_DOT" at line 1 with value ".".');

test('parse fails when table with pound', function (): void {
    $this->parser->parse("[key#group]\nanswer = 42");
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_HASH" at line 1 with value "#".');

test('parse fails when text after table', function (): void {
    $this->parser->parse('[error] this shouldn\'t be here');
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "this".');

test('parse fails when table nested brackets open', function (): void {
    $toml = <<<'toml'
        [a[b]
        zyx = 42
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_LEFT_SQUARE_BRAKET" at line 1 with value "[".');

test('parse fails when table nested brackets close', function (): void {
    $toml = <<<'toml'
        [a]b]
        zyx = 42
toml;
    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_UNQUOTED_KEY" at line 1 with value "b".');

test('parse fails when inline table with newline', function (): void {
    $toml = <<<'toml'
        name = { first = "Tom",
	           last = "Preston-Werner"
        }
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_NEWLINE" at line 1');

test('parse fails when table array with same name of table', function (): void {
    $toml = <<<'toml'
        [[fruit]]
        name = "apple"

        [[fruit.variety]]
        name = "red delicious"

        # This table conflicts with the previous table
        [fruit.variety]
        name = "granny smith"
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'The key "fruit.variety" has already been defined previously.');

test('parse fails when table array malformed empty', function (): void {
    $toml = <<<'toml'
        [[]]
        name = "Born to Run"
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_RIGHT_SQUARE_BRAKET" at line 1 with value "]".');

test('parse fails when table array malformed bracket', function (): void {
    $toml = <<<'toml'
        [[albums]
        name = "Born to Run"
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'Syntax error: unexpected token "T_NEWLINE" at line 1');

test('parse fails when table array implicit', function (): void {
    $toml = <<<'toml'
        # This test is a bit tricky. It should fail because the first use of
        # `[[albums.songs]]` without first declaring `albums` implies that `albums`
        # must be a table. The alternative would be quite weird. Namely, it wouldn't
        # comply with the TOML spec: "Each double-bracketed sub-table will belong to
        # the most *recently* defined table element *above* it."
        #
        # This is in contrast to the *valid* test, table-array-implicit where
        # `[[albums.songs]]` works by itself, so long as `[[albums]]` isn't declared
        # later. (Although, `[albums]` could be.)
        [[albums.songs]]
        name = "Glory Days"

        [[albums]]
        name = "Born in the USA"
toml;

    $this->parser->parse($toml);
})->throws(SyntaxErrorException::class, 'The array of tables "albums" has already been defined as previous table');
