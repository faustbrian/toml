<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\Exception\EmptyEosTokenNameException;
use Cline\Toml\Exception\EmptyNewlineTokenNameException;
use Cline\Toml\Exception\LexerParseException;
use Cline\Toml\Exception\UnexpectedTokenException;
use Cline\Toml\Lexer\BasicLexer;
use Cline\Toml\Lexer\Token;
use Cline\Toml\Lexer\TokenStream;

describe('Lexer', function (): void {
    test('tokenize must recognize equal token', function (): void {
        $ts = createLexer()->tokenize('=');
        expect($ts->isNext('T_EQUAL'))->toBeTrue();
    });

    test('tokenize must recognize boolean token when there is a true value', function (): void {
        $ts = createLexer()->tokenize('true');
        expect($ts->isNext('T_BOOLEAN'))->toBeTrue();
    });

    test('tokenize must recognize boolean token when there is a false value', function (): void {
        $ts = createLexer()->tokenize('false');
        expect($ts->isNext('T_BOOLEAN'))->toBeTrue();
    });

    test('tokenize must recognize unquoted key token', function (): void {
        $ts = createLexer()->tokenize('title');
        expect($ts->isNext('T_UNQUOTED_KEY'))->toBeTrue();
    });

    test('tokenize must recognize integer token when there is a positive number', function (): void {
        $ts = createLexer()->tokenize('25');
        expect($ts->isNext('T_INTEGER'))->toBeTrue();
    });

    test('tokenize must recognize integer token when there is a negative number', function (): void {
        $ts = createLexer()->tokenize('-25');
        expect($ts->isNext('T_INTEGER'))->toBeTrue();
    });

    test('tokenize must recognize integer token when there is number with underscore separator', function (): void {
        $ts = createLexer()->tokenize('2_5');
        expect($ts->isNext('T_INTEGER'))->toBeTrue();
    });

    test('tokenize must recognize float token when there is a float number', function (): void {
        $ts = createLexer()->tokenize('2.5');
        expect($ts->isNext('T_FLOAT'))->toBeTrue();
    });

    test('tokenize must recognize float token when there is a float number with underscore separator', function (): void {
        $ts = createLexer()->tokenize('9_224_617.445_991_228_313');
        expect($ts->isNext('T_FLOAT'))->toBeTrue();
    });

    test('tokenize must recognize float token when there is a negative float number', function (): void {
        $ts = createLexer()->tokenize('-2.5');
        expect($ts->isNext('T_FLOAT'))->toBeTrue();
    });

    test('tokenize must recognize float token when there is a number with exponent', function (): void {
        $ts = createLexer()->tokenize('5e+22');
        expect($ts->isNext('T_FLOAT'))->toBeTrue();
    });

    test('tokenize must recognize float token when there is a number with exponent and underscore separator', function (): void {
        $ts = createLexer()->tokenize('1e1_000');
        expect($ts->isNext('T_FLOAT'))->toBeTrue();
    });

    test('tokenize must recognize float token when there is a float number with exponent', function (): void {
        $ts = createLexer()->tokenize('6.626e-34');
        expect($ts->isNext('T_FLOAT'))->toBeTrue();
    });

    test('tokenize must recognize datetime token when there is RFC3339 datetime', function (): void {
        $ts = createLexer()->tokenize('1979-05-27T07:32:00Z');
        expect($ts->isNext('T_DATE_TIME'))->toBeTrue();
    });

    test('tokenize must recognize datetime token when there is RFC3339 datetime with offset', function (): void {
        $ts = createLexer()->tokenize('1979-05-27T00:32:00-07:00');
        expect($ts->isNext('T_DATE_TIME'))->toBeTrue();
    });

    test('tokenize must recognize datetime token when there is RFC3339 datetime with offset second fraction', function (): void {
        $ts = createLexer()->tokenize('1979-05-27T00:32:00.999999-07:00');
        expect($ts->isNext('T_DATE_TIME'))->toBeTrue();
    });

    test('tokenize must recognize quotation mark', function (): void {
        $ts = createLexer()->tokenize('"');
        expect($ts->isNext('T_QUOTATION_MARK'))->toBeTrue();
    });

    test('tokenize must recognize 3 quotation mark', function (): void {
        $ts = createLexer()->tokenize('"""');
        expect($ts->isNextSequence(['T_3_QUOTATION_MARK', 'T_EOS']))->toBeTrue();
    });

    test('tokenize must recognize apostrophe', function (): void {
        $ts = createLexer()->tokenize("'");
        expect($ts->isNext('T_APOSTROPHE'))->toBeTrue();
    });

    test('tokenize must recognize 3 apostrophe', function (): void {
        $ts = createLexer()->tokenize("'''");
        expect($ts->isNext('T_3_APOSTROPHE'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is backspace', function (): void {
        $ts = createLexer()->tokenize('\b');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is tab', function (): void {
        $ts = createLexer()->tokenize('\t');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is linefeed', function (): void {
        $ts = createLexer()->tokenize('\n');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is formfeed', function (): void {
        $ts = createLexer()->tokenize('\f');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is carriage return', function (): void {
        $ts = createLexer()->tokenize('\r');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is quote', function (): void {
        $ts = createLexer()->tokenize('\"');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is backslash', function (): void {
        $ts = createLexer()->tokenize('\\\\');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is unicode using four characters', function (): void {
        $ts = createLexer()->tokenize('\u00E9');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize escaped character when there is unicode using eight characters', function (): void {
        $ts = createLexer()->tokenize('\U00E90000');
        expect($ts->isNext('T_ESCAPED_CHARACTER'))->toBeTrue();
    });

    test('tokenize must recognize basic unescaped string', function (): void {
        $ts = createLexer()->tokenize('@text');
        expect($ts->isNextSequence(['T_BASIC_UNESCAPED', 'T_EOS']))->toBeTrue();
    });

    test('tokenize must recognize hash', function (): void {
        $ts = createLexer()->tokenize('#');
        expect($ts->isNext('T_HASH'))->toBeTrue();
    });

    test('tokenize must recognize escape', function (): void {
        $ts = createLexer()->tokenize('\\');
        expect($ts->isNextSequence(['T_ESCAPE', 'T_EOS']))->toBeTrue();
    });

    test('tokenize must recognize escape and escaped character', function (): void {
        $ts = createLexer()->tokenize('\\ \b');
        expect($ts->isNextSequence(['T_ESCAPE', 'T_SPACE', 'T_ESCAPED_CHARACTER', 'T_EOS']))->toBeTrue();
    });

    test('tokenize must recognize left square bracket', function (): void {
        $ts = createLexer()->tokenize('[');
        expect($ts->isNext('T_LEFT_SQUARE_BRAKET'))->toBeTrue();
    });

    test('tokenize must recognize right square bracket', function (): void {
        $ts = createLexer()->tokenize(']');
        expect($ts->isNext('T_RIGHT_SQUARE_BRAKET'))->toBeTrue();
    });

    test('tokenize must recognize dot', function (): void {
        $ts = createLexer()->tokenize('.');
        expect($ts->isNext('T_DOT'))->toBeTrue();
    });

    test('tokenize must recognize left curly brace', function (): void {
        $ts = createLexer()->tokenize('{');
        expect($ts->isNext('T_LEFT_CURLY_BRACE'))->toBeTrue();
    });

    test('tokenize must recognize right curly brace', function (): void {
        $ts = createLexer()->tokenize('}');
        expect($ts->isNext('T_RIGHT_CURLY_BRACE'))->toBeTrue();
    });
});

describe('TokenStream', function (): void {
    test('matchNext returns token value when token matches', function (): void {
        $ts = createLexer()->tokenize('=');
        $value = $ts->matchNext('T_EQUAL');

        expect($value)->toBe('=');
    });

    test('matchNext throws UnexpectedTokenException when token does not match', function (): void {
        $ts = createLexer()->tokenize('=');
        $ts->matchNext('T_SPACE');
    })->throws(UnexpectedTokenException::class);

    test('isNext returns false when no token available', function (): void {
        $ts = new TokenStream([]);

        expect($ts->isNext('T_EQUAL'))->toBeFalse();
    });

    test('isNextAny returns false when no token available', function (): void {
        $ts = new TokenStream([]);

        expect($ts->isNextAny(['T_EQUAL', 'T_SPACE']))->toBeFalse();
    });

    test('getAll returns all tokens in the stream', function (): void {
        $ts = createLexer()->tokenize('key = value');

        expect($ts->getAll())->toBeArray();
        expect($ts->getAll())->not->toBeEmpty();
    });

    test('hasPendingTokens returns false for empty stream', function (): void {
        $ts = new TokenStream([]);

        expect($ts->hasPendingTokens())->toBeFalse();
    });

    test('hasPendingTokens returns true when tokens remain', function (): void {
        $ts = createLexer()->tokenize('=');

        expect($ts->hasPendingTokens())->toBeTrue();
    });

    test('hasPendingTokens returns false after consuming all tokens', function (): void {
        $token = new Token('=', 'T_EQUAL', 1);
        $ts = new TokenStream([$token]);

        $ts->moveNext(); // consume the only token

        expect($ts->hasPendingTokens())->toBeFalse();
    });

    test('reset rewinds the stream to the beginning', function (): void {
        $ts = createLexer()->tokenize('= =');
        $ts->moveNext(); // consume first token
        $ts->reset();

        expect($ts->isNext('T_EQUAL'))->toBeTrue();
    });
});

describe('BasicLexer', function (): void {
    test('setNewlineTokenName throws exception for empty name', function (): void {
        $lexer = new BasicLexer(['/^(=)/' => 'T_EQUAL']);
        $lexer->setNewlineTokenName('');
    })->throws(EmptyNewlineTokenNameException::class);

    test('setEosTokenName throws exception for empty name', function (): void {
        $lexer = new BasicLexer(['/^(=)/' => 'T_EQUAL']);
        $lexer->setEosTokenName('');
    })->throws(EmptyEosTokenNameException::class);

    test('setNewlineTokenName allows custom name', function (): void {
        $lexer = new BasicLexer(['/^(=)/' => 'T_EQUAL']);
        $result = $lexer->setNewlineTokenName('CUSTOM_NEWLINE');

        expect($result)->toBe($lexer);
    });

    test('setEosTokenName allows custom name', function (): void {
        $lexer = new BasicLexer(['/^(=)/' => 'T_EQUAL']);
        $result = $lexer->setEosTokenName('CUSTOM_EOS');

        expect($result)->toBe($lexer);
    });

    test('generateNewlineTokens creates newline tokens between lines', function (): void {
        $lexer = new BasicLexer(['/^(=)/' => 'T_EQUAL']);
        $lexer->generateNewlineTokens();

        $ts = $lexer->tokenize("=\n=");

        $ts->moveNext(); // T_EQUAL
        expect($ts->isNext('T_NEWLINE'))->toBeTrue();
    });

    test('tokenize throws LexerParseException for unrecognized input', function (): void {
        $lexer = new BasicLexer(['/^(=)/' => 'T_EQUAL']);
        $lexer->tokenize('invalid');
    })->throws(LexerParseException::class);
});

describe('Token', function (): void {
    test('token stores and returns value', function (): void {
        $token = new Token('hello', 'T_STRING', 1);

        expect($token->getValue())->toBe('hello');
    });

    test('token stores and returns name', function (): void {
        $token = new Token('hello', 'T_STRING', 1);

        expect($token->getName())->toBe('T_STRING');
    });

    test('token stores and returns line number', function (): void {
        $token = new Token('hello', 'T_STRING', 42);

        expect($token->getLine())->toBe(42);
    });
});
