<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Date;

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
describe('Parser', function (): void {
    test('parse must return an empty array when empty input', function (): void {
        $array = createParser()->parse('');

        expect($array)->toBe([]);
    });

    test('parse must parse booleans', function (): void {
        $toml = <<<'toml'
        t = true
        f = false
toml;
        $array = createParser()->parse($toml);

        expect($array)->toBe([
            't' => true,
            'f' => false,
        ]);
    });

    test('parse must parse integers', function (): void {
        $toml = <<<'toml'
        answer = 42
        neganswer = -42
        positive = +90
        underscore = 1_2_3_4_5
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'answer' => 42,
            'neganswer' => -42,
            'positive' => 90,
            'underscore' => 12_345,
        ]);
    });

    test('parse must parse long integers', function (): void {
        $toml = <<<'toml'
        answer = 9223372036854775807
        neganswer = -9223372036854775808
toml;

        $array = createParser()->parse($toml);

        expect($array)->toEqual([
            'answer' => 9_223_372_036_854_775_807,
            'neganswer' => -9_223_372_036_854_775_808,
        ]);
    });

    test('parse must parse floats', function (): void {
        $toml = <<<'toml'
        pi = 3.14
        negpi = -3.14
        positive = +1.01
        exponent1 = 5e+22
        exponent2 = 1e6
        exponent3 = -2E-2
        exponent4 = 6.626e-34
        underscore = 6.6_26e-3_4
toml;

        $array = createParser()->parse($toml);

        expect($array)->toEqual([
            'pi' => 3.14,
            'negpi' => -3.14,
            'positive' => 1.01,
            'exponent1' => 4.999_999_999_999_999_6E+22,
            'exponent2' => 1_000_000.0,
            'exponent3' => -0.02,
            'exponent4' => 6.625_999_999_999_999_8E-34,
            'underscore' => 6.625_999_999_999_999_8E-34,
        ]);
    });

    test('parse must parse long floats', function (): void {
        $toml = <<<'toml'
        longpi = 3.141592653589793
        neglongpi = -3.141592653589793
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'longpi' => 3.141_592_653_589_793,
            'neglongpi' => -3.141_592_653_589_793,
        ]);
    });

    test('parse must parse basic strings with a simple string', function (): void {
        $array = createParser()->parse('answer = "You are not drinking enough whisky."');

        expect($array)->toBe([
            'answer' => 'You are not drinking enough whisky.',
        ]);
    });

    test('parse must parse an empty string', function (): void {
        $array = createParser()->parse('answer = ""');

        expect($array)->toBe([
            'answer' => '',
        ]);
    });

    test('parse must parse strings with escaped characters', function (): void {
        $toml = <<<'toml'
        backspace = "This string has a \b backspace character."
        tab = "This string has a \t tab character."
        newline = "This string has a \n new line character."
        formfeed = "This string has a \f form feed character."
        carriage = "This string has a \r carriage return character."
        quote = "This string has a \" quote character."
        backslash = "This string has a \\ backslash character."
        notunicode1 = "This string does not have a unicode \\u escape."
        notunicode2 = "This string does not have a unicode \\u0075 escape."
toml;

        $array = createParser()->parse($toml);
        expect($array)->toBe([
            'backspace' => 'This string has a \\b backspace character.',
            'tab' => "This string has a \t tab character.",
            'newline' => "This string has a \n new line character.",
            'formfeed' => "This string has a \f form feed character.",
            'carriage' => "This string has a \r carriage return character.",
            'quote' => 'This string has a " quote character.',
            'backslash' => 'This string has a \\ backslash character.',
            'notunicode1' => 'This string does not have a unicode \\u escape.',
            'notunicode2' => 'This string does not have a unicode \\u0075 escape.',
        ]);
    });

    test('parse must parse strings with pound', function (): void {
        $toml = <<<'toml'
        pound = "We see no # comments here."
        poundcomment = "But there are # some comments here." # Did I # mess you up?
toml;

        $array = createParser()->parse($toml);
        expect($array)->toBe([
            'pound' => 'We see no # comments here.',
            'poundcomment' => 'But there are # some comments here.',
        ]);
    });

    test('parse must parse with unicode character escaped', function (): void {
        $toml = <<<'toml'
        answer4 = "\u03B4"
        answer8 = "\U000003B4"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'answer4' => json_decode('"\u03B4"'),
            'answer8' => json_decode('"\u0000\u03B4"'),
        ]);
    });

    test('parse must parse string with a literal unicode character', function (): void {
        $array = createParser()->parse('answer = "δ"');

        expect($array)->toBe([
            'answer' => 'δ',
        ]);
    });

    test('parse must parse multiline strings', function (): void {
        $toml = <<<'toml'
        multiline_empty_one = """"""
        multiline_empty_two = """
"""
        multiline_empty_three = """\
    """
        multiline_empty_four = """\
           \
           \
           """

        equivalent_one = "The quick brown fox jumps over the lazy dog."
        equivalent_two = """
The quick brown \


          fox jumps over \
            the lazy dog."""

        equivalent_three = """\
               The quick brown \
               fox jumps over \
               the lazy dog.\
               """
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'multiline_empty_one' => '',
            'multiline_empty_two' => '',
            'multiline_empty_three' => '',
            'multiline_empty_four' => '',
            'equivalent_one' => 'The quick brown fox jumps over the lazy dog.',
            'equivalent_two' => 'The quick brown fox jumps over the lazy dog.',
            'equivalent_three' => 'The quick brown fox jumps over the lazy dog.',
        ]);
    });

    test('parse must parse literal strings', function (): void {
        $toml = <<<'toml'
        backspace = 'This string has a \b backspace character.'
        tab = 'This string has a \t tab character.'
        newline = 'This string has a \n new line character.'
        formfeed = 'This string has a \f form feed character.'
        carriage = 'This string has a \r carriage return character.'
        slash = 'This string has a \/ slash character.'
        backslash = 'This string has a \\ backslash character.'
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'backspace' => 'This string has a \b backspace character.',
            'tab' => 'This string has a \t tab character.',
            'newline' => 'This string has a \n new line character.',
            'formfeed' => 'This string has a \f form feed character.',
            'carriage' => 'This string has a \r carriage return character.',
            'slash' => 'This string has a \/ slash character.',
            'backslash' => 'This string has a \\\\ backslash character.',
        ]);
    });

    test('parse must parse multiline literal strings', function (): void {
        $toml = <<<'toml'
        oneline = '''This string has a ' quote character.'''
        firstnl = '''
This string has a ' quote character.'''
multiline = '''
This string
has ' a quote character
and more than
one newline
in it.'''
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'oneline' => "This string has a ' quote character.",
            'firstnl' => "This string has a ' quote character.",
            'multiline' => "This string\nhas ' a quote character\nand more than\none newline\nin it.",
        ]);
    });

    test('datetime', function (): void {
        $toml = <<<'toml'
        bestdayever = 1987-07-05T17:45:00Z
        bestdayever2 = 1979-05-27T00:32:00-07:00
        bestdayever3 = 1979-05-27T00:32:00.999999-07:00
        bestdayever4 = 1979-05-27T07:32:00
        bestdayever5 = 1979-05-27T00:32:00.999999
        bestdayever6 = 1979-05-27

toml;

        $array = createParser()->parse($toml);

        expect($array)->toEqual([
            'bestdayever' => Date::parse('1987-07-05T17:45:00Z'),
            'bestdayever2' => Date::parse('1979-05-27T00:32:00-07:00'),
            'bestdayever3' => Date::parse('1979-05-27T00:32:00.999999-07:00'),
            'bestdayever4' => Date::parse('1979-05-27T07:32:00'),
            'bestdayever5' => Date::parse('1979-05-27T00:32:00.999999'),
            'bestdayever6' => Date::parse('1979-05-27'),
        ]);
    });

    test('parse must parse arrays with no spaces', function (): void {
        $array = createParser()->parse('ints = [1,2,3]');

        expect($array)->toBe([
            'ints' => [1, 2, 3],
        ]);
    });

    test('parse must parse heterogeneous arrays', function (): void {
        $array = createParser()->parse('mixed = [[1, 2], ["a", "b"], [1.1, 2.1]]');

        expect($array)->toBe([
            'mixed' => [
                [1, 2],
                ['a', 'b'],
                [1.1, 2.1],
            ],
        ]);
    });

    test('parse must parse arrays nested', function (): void {
        $array = createParser()->parse('nest = [["a"], ["b"]]');

        expect($array)->toBe([
            'nest' => [
                ['a'],
                ['b'],
            ],
        ]);
    });

    test('array empty', function (): void {
        $array = createParser()->parse('thevoid = [[[[[]]]]]');

        expect($array)->toBe([
            'thevoid' => [
                [
                    [
                        [
                            [],
                        ],
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse arrays', function (): void {
        $toml = <<<'toml'
        ints = [1, 2, 3]
        floats = [1.1, 2.1, 3.1]
        strings = ["a", "b", "c"]
        allStrings = ["all", 'strings', """are the same""", '''type''']
        MultilineBasicString = ["all", """
Roses are red
Violets are blue""",]
        dates = [
          1987-07-05T17:45:00Z,
          1979-05-27T07:32:00Z,
          2006-06-01T11:00:00Z,
        ]
toml;

        $array = createParser()->parse($toml);

        expect($array)->toEqual([
            'ints' => [1, 2, 3],
            'floats' => [1.1, 2.1, 3.1],
            'strings' => ['a', 'b', 'c'],
            'allStrings' => ['all', 'strings', 'are the same', 'type'],
            'MultilineBasicString' => [
                'all',
                "Roses are red\nViolets are blue",
            ],
            'dates' => [
                Date::parse('1987-07-05T17:45:00Z'),
                Date::parse('1979-05-27T07:32:00Z'),
                Date::parse('2006-06-01T11:00:00Z'),
            ],
        ]);
    });

    test('parse must parse a key without name spaces around equal sign', function (): void {
        $array = createParser()->parse('answer=42');

        expect($array)->toBe([
            'answer' => 42,
        ]);
    });

    test('parse must parse key with space', function (): void {
        $array = createParser()->parse('"a b" = 1');

        expect($array)->not->toBeNull();
        expect($array)->toBe([
            'a b' => 1,
        ]);
    });

    test('parse must parse key with special characters', function (): void {
        $array = createParser()->parse('"~!@$^&*()_+-`1234567890[]|/?><.,;:\'" = 1');

        expect($array)->toBe([
            '~!@$^&*()_+-`1234567890[]|/?><.,;:\'' => 1,
        ]);
    });

    test('parse must parse bare integer keys', function (): void {
        $toml = <<<'toml'
        [sequence]
        -1 = 'detect person'
        0 = 'say hello'
        1 = 'chat'
        10 = 'say bye'
toml;
        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'sequence' => [
                '-1' => 'detect person',
                '0' => 'say hello',
                '1' => 'chat',
                '10' => 'say bye',
            ],
        ]);
    });

    test('parse must parse an empty table', function (): void {
        $array = createParser()->parse('[a]');

        expect($array)->toBe([
            'a' => [],
        ]);
    });

    test('parse must parse a table with a whitespace in the name', function (): void {
        $array = createParser()->parse('["valid key"]');

        expect($array)->toBe([
            'valid key' => [],
        ]);
    });

    test('parse must parse a table with a quoted name', function (): void {
        $toml = <<<'toml'
        [dog."tater.man"]
        type = "pug"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'dog' => [
                'tater.man' => [
                    'type' => 'pug',
                ],
            ],
        ]);
    });

    test('parse must parse a table with a pound in the name', function (): void {
        $toml = <<<'toml'
        ["key#group"]
        answer = 42
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'key#group' => [
                'answer' => 42,
            ],
        ]);
    });

    test('parse must parse a table and a subtable empties', function (): void {
        $toml = <<<'toml'
        [a]
        [a.b]
toml;
        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'a' => [
                'b' => [],
            ],
        ]);
    });

    test('parse must parse a table with implicit groups', function (): void {
        $toml = <<<'toml'
        [a.b.c]
        answer = 42
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'a' => [
                'b' => [
                    'c' => [
                        'answer' => 42,
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse a implicit and explicit after table', function (): void {
        $toml = <<<'toml'
        [a.b.c]
        answer = 42

        [a]
        better = 43
toml;

        $array = createParser()->parse($toml);

        expect($array)->toEqual([
            'a' => [
                'better' => 43,
                'b' => [
                    'c' => [
                        'answer' => 42,
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse implicit and explicit table before', function (): void {
        $toml = <<<'toml'
        [a]
        better = 43

        [a.b.c]
        answer = 42
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'a' => [
                'better' => 43,
                'b' => [
                    'c' => [
                        'answer' => 42,
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse inline table empty', function (): void {
        $array = createParser()->parse('name = {}');

        expect($array)->toBe([
            'name' => [],
        ]);
    });

    test('parse must parse inline table one element', function (): void {
        $array = createParser()->parse('name = { first = "Tom" }');

        expect($array)->toBe([
            'name' => [
                'first' => 'Tom',
            ],
        ]);
    });

    test('parse must parse an inline table defined in a table', function (): void {
        $toml = <<<'toml'
        [tab1]
        key1 = {name='Donald Duck'}
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'tab1' => [
                'key1' => [
                    'name' => 'Donald Duck',
                ],
            ],
        ]);
    });

    test('parse must parse inline table examples', function (): void {
        $toml = <<<'toml'
name = { first = "Tom", last = "Preston-Werner" }
point = { x = 1, y = 2 }
strings = { key1 = """
Roses are red
Violets are blue""", key2 = """
The quick brown \


  fox jumps over \
    the lazy dog.""" }
inline = { x = 1, y = { a = 2, "b.deep" = 'my value' } }
another = {number = 1}
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'name' => [
                'first' => 'Tom',
                'last' => 'Preston-Werner',
            ],
            'point' => [
                'x' => 1,
                'y' => 2,
            ],
            'strings' => [
                'key1' => "Roses are red\nViolets are blue",
                'key2' => 'The quick brown fox jumps over the lazy dog.',
            ],
            'inline' => [
                'x' => 1,
                'y' => [
                    'a' => 2,
                    'b.deep' => 'my value',
                ],
            ],
            'another' => [
                'number' => 1,
            ],
        ]);
    });

    test('parse must parse table array implicit', function (): void {
        $toml = <<<'toml'
        [[albums.songs]]
        name = "Glory Days"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'albums' => [
                'songs' => [
                    [
                        'name' => 'Glory Days',
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse table array one', function (): void {
        $toml = <<<'toml'
        [[people]]
        first_name = "Bruce"
        last_name = "Springsteen"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'people' => [
                [
                    'first_name' => 'Bruce',
                    'last_name' => 'Springsteen',
                ],
            ],
        ]);
    });

    test('parse must parse table array many', function (): void {
        $toml = <<<'toml'
        [[people]]
        first_name = "Bruce"
        last_name = "Springsteen"

        [[people]]
        first_name = "Eric"
        last_name = "Clapton"

        [[people]]
        first_name = "Bob"
        last_name = "Seger"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'people' => [
                [
                    'first_name' => 'Bruce',
                    'last_name' => 'Springsteen',
                ],
                [
                    'first_name' => 'Eric',
                    'last_name' => 'Clapton',
                ],
                [
                    'first_name' => 'Bob',
                    'last_name' => 'Seger',
                ],
            ],
        ]);
    });

    test('parse must parse table array nest', function (): void {
        $toml = <<<'toml'
        [[albums]]
        name = "Born to Run"

          [[albums.songs]]
          name = "Jungleland"

          [[albums.songs]]
          name = "Meeting Across the River"

        [[albums]]
        name = "Born in the USA"

          [[albums.songs]]
          name = "Glory Days"

          [[albums.songs]]
          name = "Dancing in the Dark"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'albums' => [
                [
                    'name' => 'Born to Run',
                    'songs' => [
                        ['name' => 'Jungleland'],
                        ['name' => 'Meeting Across the River'],
                    ],
                ],
                [
                    'name' => 'Born in the USA',
                    'songs' => [
                        ['name' => 'Glory Days'],
                        ['name' => 'Dancing in the Dark'],
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse a table and array of tables', function (): void {
        $toml = <<<'toml'
        [fruit]
        name = "apple"

        [[fruit.variety]]
        name = "red delicious"

        [[fruit.variety]]
        name = "granny smith"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'fruit' => [
                'name' => 'apple',
                'variety' => [
                    ['name' => 'red delicious'],
                    ['name' => 'granny smith'],
                ],
            ],
        ]);
    });

    test('parse must parse tables contained within array tables', function (): void {
        $toml = <<<'toml'
        [[tls]]
        entrypoints = ["https"]
        [tls.certificate]
            certFile = "certs/foo.crt"
            keyFile  = "keys/foo.key"

        [[tls]]
        entrypoints = ["https"]
        [tls.certificate]
            certFile = "certs/bar.crt"
            keyFile  = "keys/bar.key"
toml;

        $array = createParser()->parse($toml);

        expect($array)->toBe([
            'tls' => [
                [
                    'entrypoints' => ['https'],
                    'certificate' => [
                        'certFile' => 'certs/foo.crt',
                        'keyFile' => 'keys/foo.key',
                    ],
                ],
                [
                    'entrypoints' => ['https'],
                    'certificate' => [
                        'certFile' => 'certs/bar.crt',
                        'keyFile' => 'keys/bar.key',
                    ],
                ],
            ],
        ]);
    });

    test('parse must parse comments everywhere', function (): void {
        $toml = <<<'toml'
        # Top comment.
          # Top comment.
        # Top comment.

        # [no-extraneous-groups-please]

        [group] # Comment
        answer = 42 # Comment
        # no-extraneous-keys-please = 999
        # Inbetween comment.
        more = [ # Comment
          # What about multiple # comments?
          # Can you handle it?
          #
                  # Evil.
        # Evil.
          42, 42, # Comments within arrays are fun.
          # What about multiple # comments?
          # Can you handle it?
          #
                  # Evil.
        # Evil.
        # ] Did I fool you?
        ] # Hopefully not.
toml;

        $array = createParser()->parse($toml);

        expect($array)->not->toBeNull();
        expect($array['group'])->toHaveKey('answer');
        expect($array['group'])->toHaveKey('more');
        expect($array['group']['answer'])->toBe(42);
        expect($array['group']['more'][0])->toBe(42);
        expect($array['group']['more'][1])->toBe(42);
    });

    test('parse must parse a simple example', function (): void {
        $toml = <<<'toml'
        best-day-ever = 1987-07-05T17:45:00Z
        emptyName = ""

        [numtheory]
        boring = false
        perfection = [6, 28, 496]
toml;

        $array = createParser()->parse($toml);

        expect($array)->toEqual([
            'best-day-ever' => Date::parse('1987-07-05T17:45:00Z'),
            'emptyName' => '',
            'numtheory' => [
                'boring' => false,
                'perfection' => [6, 28, 496],
            ],
        ]);
    });
});
