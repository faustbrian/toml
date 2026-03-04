<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use Cline\Toml\Exception\InvalidUtf8Exception;
use Cline\Toml\Exception\ParserSyntaxErrorException;
use Cline\Toml\Exception\ParserUnexpectedTokenException;
use Cline\Toml\Lexer\LexerInterface;
use Cline\Toml\Lexer\Token;
use Cline\Toml\Lexer\TokenStream;
use DateTime;

use const JSON_THROW_ON_ERROR;

use function assert;
use function is_scalar;
use function is_string;
use function json_decode;
use function mb_strlen;
use function preg_match;
use function sprintf;
use function str_replace;

/**
 * Transforms tokenized TOML input into PHP arrays following TOML specification v0.4.0.
 *
 * The Parser processes a stream of tokens from the Lexer to construct nested PHP arrays
 * representing TOML data structures. It validates syntax, enforces type constraints,
 * prevents duplicate keys, and handles all TOML constructs including tables, arrays
 * of tables, inline tables, and all value types (strings, numbers, dates, arrays).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Parser
{
    /**
     * Token types that are invalid within basic strings.
     *
     * Basic strings must not contain unescaped control characters, literal newlines,
     * or end-of-stream markers. These tokens cause parsing to fail with a syntax error.
     *
     * @var array<int, string>
     */
    private static array $tokensNotAllowedInBasicStrings = [
        'T_ESCAPE',
        'T_NEWLINE',
        'T_EOS',
    ];

    /**
     * Token types that are invalid within literal strings.
     *
     * Literal strings (single quotes) cannot contain newlines or end-of-stream
     * markers, though they preserve all other characters without escape processing.
     *
     * @var array<int, string>
     */
    private static array $tokensNotAllowedInLiteralStrings = [
        'T_NEWLINE',
        'T_EOS',
    ];

    /**
     * Tracks all defined keys to prevent duplicates and validate table hierarchies.
     */
    private KeyStore $keyStore;

    /**
     * Builder for constructing the nested array structure from parsed TOML data.
     */
    private TomlArray $tomlArray;

    /**
     * Create a new TOML parser.
     *
     * @param LexerInterface $lexer Lexer instance that tokenizes TOML input strings
     *                              into processable token streams for parsing.
     */
    public function __construct(
        private readonly LexerInterface $lexer,
    ) {}

    /**
     * Parse TOML input string into a PHP array.
     *
     * Validates UTF-8 encoding, normalizes line endings and tabs, tokenizes the input,
     * and processes all TOML constructs into a nested associative array. Keys become
     * array keys, and values are converted to appropriate PHP types (string, int,
     * float, bool, DateTime, array).
     *
     * @param string $input Raw TOML content as a UTF-8 string
     *
     * @throws InvalidUtf8Exception           If the input contains invalid UTF-8 sequences
     * @throws ParserSyntaxErrorException     If TOML syntax rules are violated
     * @throws ParserUnexpectedTokenException If unexpected tokens are encountered
     * @return array<string, mixed>           Associative array representing the parsed TOML structure
     */
    public function parse(string $input): array
    {
        if (preg_match('//u', $input) === false) {
            throw InvalidUtf8Exception::create();
        }

        $input = str_replace(["\r\n", "\r"], "\n", $input);
        $input = str_replace("\t", ' ', $input);

        $ts = $this->lexer->tokenize($input);

        return $this->parseImplementation($ts);
    }

    /**
     * Core parsing loop that processes all tokens in the stream.
     *
     * Initializes internal state and iterates through the token stream,
     * processing each top-level expression until all tokens are consumed.
     *
     * @param TokenStream $ts Token stream to parse
     *
     * @return array<string, mixed> Complete parsed TOML structure
     */
    private function parseImplementation(TokenStream $ts): array
    {
        $this->keyStore = new KeyStore();
        $this->tomlArray = new TomlArray();

        while ($ts->hasPendingTokens()) {
            $this->processExpression($ts);
        }

        return $this->tomlArray->getArray();
    }

    private function processExpression(TokenStream $ts): void
    {
        if ($ts->isNext('T_HASH')) {
            $this->parseComment($ts);
        } elseif ($ts->isNextAny(['T_QUOTATION_MARK', 'T_UNQUOTED_KEY', 'T_INTEGER'])) {
            $this->parseKeyValue($ts);
        } elseif ($ts->isNextSequence(['T_LEFT_SQUARE_BRAKET', 'T_LEFT_SQUARE_BRAKET'])) {
            $this->parseArrayOfTables($ts);
        } elseif ($ts->isNext('T_LEFT_SQUARE_BRAKET')) {
            $this->parseTable($ts);
        } elseif ($ts->isNextAny(['T_SPACE', 'T_NEWLINE', 'T_EOS'])) {
            $ts->moveNext();
        } else {
            $msg = 'Expected T_HASH or T_UNQUOTED_KEY.';
            $this->unexpectedTokenError($ts->moveNext(), $msg);
        }
    }

    private function parseComment(TokenStream $ts): void
    {
        $this->matchNext('T_HASH', $ts);

        while (!$ts->isNextAny(['T_NEWLINE', 'T_EOS'])) {
            $ts->moveNext();
        }
    }

    private function parseKeyValue(TokenStream $ts, bool $isFromInlineTable = false): void
    {
        $keyName = $this->parseKeyName($ts);
        $this->parseSpaceIfExists($ts);
        $this->matchNext('T_EQUAL', $ts);
        $this->parseSpaceIfExists($ts);

        $isInlineTable = $ts->isNext('T_LEFT_CURLY_BRACE');

        if ($isInlineTable) {
            if (!$this->keyStore->isValidInlineTable($keyName)) {
                $this->syntaxError(sprintf('The inline table key "%s" has already been defined previously.', $keyName));
            }

            $this->keyStore->addInlineTableKey($keyName);
        } else {
            if (!$this->keyStore->isValidKey($keyName)) {
                $this->syntaxError(sprintf('The key "%s" has already been defined previously.', $keyName));
            }

            $this->keyStore->addKey($keyName);
        }

        if ($ts->isNext('T_LEFT_SQUARE_BRAKET')) {
            $this->tomlArray->addKeyValue($keyName, $this->parseArray($ts));
        } elseif ($isInlineTable) {
            $this->parseInlineTable($ts, $keyName);
        } else {
            $this->tomlArray->addKeyValue($keyName, $this->parseSimpleValue($ts)->value);
        }

        if ($isFromInlineTable) {
            return;
        }

        $this->parseSpaceIfExists($ts);
        $this->parseCommentIfExists($ts);
        $this->errorIfNextIsNotNewlineOrEOS($ts);
    }

    private function parseKeyName(TokenStream $ts): string
    {
        if ($ts->isNext('T_UNQUOTED_KEY')) {
            return $this->matchNext('T_UNQUOTED_KEY', $ts);
        }

        if ($ts->isNext('T_INTEGER')) {
            return (string) $this->parseInteger($ts);
        }

        return $this->parseBasicString($ts);
    }

    /**
     * Parse a scalar value from the token stream.
     *
     * Detects and parses boolean, integer, float, string, or datetime values
     * based on the next token type. Returns a structure containing both the
     * parsed value and its type for array homogeneity validation.
     *
     * @param TokenStream $ts Token stream positioned at a value token
     *
     * @throws ParserUnexpectedTokenException     If the next token is not a valid value type
     * @return object{value: mixed, type: string} Object with 'value' (parsed data) and
     *                                            'type' (string identifier for validation)
     */
    private function parseSimpleValue(TokenStream $ts): object
    {
        if ($ts->isNext('T_BOOLEAN')) {
            $type = 'boolean';
            $value = $this->parseBoolean($ts);
        } elseif ($ts->isNext('T_INTEGER')) {
            $type = 'integer';
            $value = $this->parseInteger($ts);
        } elseif ($ts->isNext('T_FLOAT')) {
            $type = 'float';
            $value = $this->parseFloat($ts);
        } elseif ($ts->isNext('T_QUOTATION_MARK')) {
            $type = 'string';
            $value = $this->parseBasicString($ts);
        } elseif ($ts->isNext('T_3_QUOTATION_MARK')) {
            $type = 'string';
            $value = $this->parseMultilineBasicString($ts);
        } elseif ($ts->isNext('T_APOSTROPHE')) {
            $type = 'string';
            $value = $this->parseLiteralString($ts);
        } elseif ($ts->isNext('T_3_APOSTROPHE')) {
            $type = 'string';
            $value = $this->parseMultilineLiteralString($ts);
        } elseif ($ts->isNext('T_DATE_TIME')) {
            $type = 'datetime';
            $value = $this->parseDatetime($ts);
        } else {
            $this->unexpectedTokenError(
                $ts->moveNext(),
                'Expected boolean, integer, long, string or datetime.',
            );
        }

        $valueStruct = new class()
        {
            public mixed $value;

            public string $type;
        };

        $valueStruct->value = $value;
        $valueStruct->type = $type;

        return $valueStruct;
    }

    private function parseBoolean(TokenStream $ts): bool
    {
        return $this->matchNext('T_BOOLEAN', $ts) === 'true';
    }

    private function parseInteger(TokenStream $ts): int
    {
        $token = $ts->moveNext();
        assert($token instanceof Token);
        $value = $token->getValue();

        if (preg_match('/([^\d]_[^\d])|(_$)/', $value)) {
            $this->syntaxError(
                'Invalid integer number: underscore must be surrounded by at least one digit.',
                $token,
            );
        }

        $value = str_replace('_', '', $value);

        if (preg_match('/^0\d+/', $value)) {
            $this->syntaxError(
                'Invalid integer number: leading zeros are not allowed.',
                $token,
            );
        }

        return (int) $value;
    }

    private function parseFloat(TokenStream $ts): float
    {
        $token = $ts->moveNext();
        assert($token instanceof Token);
        $value = $token->getValue();

        if (preg_match('/([^\d]_[^\d])|_[eE]|[eE]_|(_$)/', $value)) {
            $this->syntaxError(
                'Invalid float number: underscore must be surrounded by at least one digit.',
                $token,
            );
        }

        $value = str_replace('_', '', $value);

        if (preg_match('/^0\d+/', $value)) {
            $this->syntaxError(
                'Invalid float number: leading zeros are not allowed.',
                $token,
            );
        }

        return (float) $value;
    }

    private function parseBasicString(TokenStream $ts): string
    {
        $this->matchNext('T_QUOTATION_MARK', $ts);

        $result = '';

        while (!$ts->isNext('T_QUOTATION_MARK')) {
            if ($ts->isNextAny(self::$tokensNotAllowedInBasicStrings)) {
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            if ($ts->isNext('T_ESCAPED_CHARACTER')) {
                $value = $this->parseEscapedCharacter($ts);
            } else {
                $nextToken = $ts->moveNext();
                assert($nextToken instanceof Token);
                $value = $nextToken->getValue();
            }

            $result .= $value;
        }

        $this->matchNext('T_QUOTATION_MARK', $ts);

        return $result;
    }

    private function parseMultilineBasicString(TokenStream $ts): string
    {
        $this->matchNext('T_3_QUOTATION_MARK', $ts);

        $result = '';

        if ($ts->isNext('T_NEWLINE')) {
            $ts->moveNext();
        }

        while (!$ts->isNext('T_3_QUOTATION_MARK')) {
            if ($ts->isNext('T_EOS')) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_QUOTATION_MARK".');
            }

            if ($ts->isNext('T_ESCAPE')) {
                $ts->skipWhileAny(['T_ESCAPE', 'T_SPACE', 'T_NEWLINE']);
            }

            if ($ts->isNext('T_EOS')) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_QUOTATION_MARK".');
            }

            if ($ts->isNext('T_3_QUOTATION_MARK')) {
                continue;
            }

            if ($ts->isNext('T_ESCAPED_CHARACTER')) {
                $value = $this->parseEscapedCharacter($ts);
            } else {
                $nextToken = $ts->moveNext();
                assert($nextToken instanceof Token);
                $value = $nextToken->getValue();
            }

            $result .= $value;
        }

        $this->matchNext('T_3_QUOTATION_MARK', $ts);

        return $result;
    }

    private function parseLiteralString(TokenStream $ts): string
    {
        $this->matchNext('T_APOSTROPHE', $ts);

        $result = '';

        while (!$ts->isNext('T_APOSTROPHE')) {
            if ($ts->isNextAny(self::$tokensNotAllowedInLiteralStrings)) {
                $this->unexpectedTokenError($ts->moveNext(), 'This character is not valid.');
            }

            $nextToken = $ts->moveNext();
            assert($nextToken instanceof Token);
            $result .= $nextToken->getValue();
        }

        $this->matchNext('T_APOSTROPHE', $ts);

        return $result;
    }

    private function parseMultilineLiteralString(TokenStream $ts): string
    {
        $this->matchNext('T_3_APOSTROPHE', $ts);

        $result = '';

        if ($ts->isNext('T_NEWLINE')) {
            $ts->moveNext();
        }

        while (!$ts->isNext('T_3_APOSTROPHE')) {
            if ($ts->isNext('T_EOS')) {
                $this->unexpectedTokenError($ts->moveNext(), 'Expected token "T_3_APOSTROPHE".');
            }

            $nextToken = $ts->moveNext();
            assert($nextToken instanceof Token);
            $result .= $nextToken->getValue();
        }

        $this->matchNext('T_3_APOSTROPHE', $ts);

        return $result;
    }

    private function parseEscapedCharacter(TokenStream $ts): string
    {
        $token = $ts->moveNext();
        assert($token instanceof Token);
        $value = $token->getValue();

        return match ($value) {
            '\b' => '\\b',
            '\t' => "\t",
            '\n' => "\n",
            '\f' => "\f",
            '\r' => "\r",
            '\"' => '"',
            '\\\\' => '\\',
            default => $this->parseUnicodeEscape($value),
        };
    }

    private function parseUnicodeEscape(string $value): string
    {
        if (mb_strlen($value) === 6) {
            $decoded = json_decode('"'.$value.'"', false, 512, JSON_THROW_ON_ERROR);
            assert(is_string($decoded));

            return $decoded;
        }

        preg_match('/\\\U([0-9a-fA-F]{4})([0-9a-fA-F]{4})/', $value, $matches);

        $decoded = json_decode('"\u'.$matches[1].'\u'.$matches[2].'"', false, 512, JSON_THROW_ON_ERROR);
        assert(is_string($decoded));

        return $decoded;
    }

    private function parseDatetime(TokenStream $ts): DateTime
    {
        $date = $this->matchNext('T_DATE_TIME', $ts);

        return new DateTime($date);
    }

    /**
     * Parse a TOML array into a PHP indexed array.
     *
     * Processes comma-separated values within square brackets, ensuring all elements
     * have the same type per TOML specification. Handles nested arrays, whitespace,
     * comments, and trailing commas. Arrays can contain any value type but not mixed types.
     *
     * @param TokenStream $ts Token stream positioned at T_LEFT_SQUARE_BRAKET
     *
     * @throws ParserSyntaxErrorException     If array contains mixed data types
     * @throws ParserUnexpectedTokenException If array syntax is invalid
     * @return array<int, mixed>              Indexed array containing homogeneous values
     */
    private function parseArray(TokenStream $ts): array
    {
        $result = [];
        $leaderType = '';
        $leaderValue = '';

        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);

        while (!$ts->isNext('T_RIGHT_SQUARE_BRAKET')) {
            $ts->skipWhileAny(['T_NEWLINE', 'T_SPACE']);
            $this->parseCommentsInsideBlockIfExists($ts);

            if ($ts->isNext('T_LEFT_SQUARE_BRAKET')) {
                if ($leaderType === '') {
                    $leaderType = 'array';
                    $leaderValue = 'array';
                }

                if ($leaderType !== 'array') {
                    $this->syntaxError(sprintf(
                        'Data types cannot be mixed in an array. Value: "%s".',
                        $leaderValue,
                    ));
                }

                $result[] = $this->parseArray($ts);
            } else {
                $valueStruct = $this->parseSimpleValue($ts);

                if ($leaderType === '') {
                    $leaderType = $valueStruct->type;
                    $leaderValue = $valueStruct->value instanceof DateTime
                        ? $valueStruct->value->format('c')
                        : (is_scalar($valueStruct->value) ? (string) $valueStruct->value : 'complex');
                }

                if ($valueStruct->type !== $leaderType) {
                    $displayValue = is_scalar($valueStruct->value) ? (string) $valueStruct->value : 'complex';
                    $this->syntaxError(sprintf(
                        'Data types cannot be mixed in an array. Value: "%s".',
                        $displayValue,
                    ));
                }

                $result[] = $valueStruct->value;
            }

            $ts->skipWhileAny(['T_NEWLINE', 'T_SPACE']);
            $this->parseCommentsInsideBlockIfExists($ts);

            if (!$ts->isNext('T_RIGHT_SQUARE_BRAKET')) {
                $this->matchNext('T_COMMA', $ts);
            }

            $ts->skipWhileAny(['T_NEWLINE', 'T_SPACE']);
            $this->parseCommentsInsideBlockIfExists($ts);
        }

        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);

        return $result;
    }

    private function parseInlineTable(TokenStream $ts, string $keyName): void
    {
        $this->matchNext('T_LEFT_CURLY_BRACE', $ts);

        $this->tomlArray->beginInlineTableKey($keyName);

        $this->parseSpaceIfExists($ts);

        if (!$ts->isNext('T_RIGHT_CURLY_BRACE')) {
            $this->parseKeyValue($ts, true);
            $this->parseSpaceIfExists($ts);
        }

        while ($ts->isNext('T_COMMA')) {
            $ts->moveNext();

            $this->parseSpaceIfExists($ts);
            $this->parseKeyValue($ts, true);
            $this->parseSpaceIfExists($ts);
        }

        $this->matchNext('T_RIGHT_CURLY_BRACE', $ts);

        $this->tomlArray->endCurrentInlineTableKey();
    }

    private function parseTable(TokenStream $ts): void
    {
        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);

        $fullTableName = $this->tomlArray->escapeKey($key = $this->parseKeyName($ts));

        while ($ts->isNext('T_DOT')) {
            $ts->moveNext();

            $key = $this->tomlArray->escapeKey($this->parseKeyName($ts));
            $fullTableName .= '.'.$key;
        }

        if (!$this->keyStore->isValidTableKey($fullTableName)) {
            $this->syntaxError(sprintf('The key "%s" has already been defined previously.', $fullTableName));
        }

        $this->keyStore->addTableKey($fullTableName);
        $this->tomlArray->addTableKey($fullTableName);
        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);

        $this->parseSpaceIfExists($ts);
        $this->parseCommentIfExists($ts);
        $this->errorIfNextIsNotNewlineOrEOS($ts);
    }

    private function parseArrayOfTables(TokenStream $ts): void
    {
        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);
        $this->matchNext('T_LEFT_SQUARE_BRAKET', $ts);
        $fullTableName = $this->tomlArray->escapeKey($this->parseKeyName($ts));
        $key = $fullTableName;

        while ($ts->isNext('T_DOT')) {
            $ts->moveNext();

            $key = $this->tomlArray->escapeKey($this->parseKeyName($ts));
            $fullTableName .= '.'.$key;
        }

        if (!$this->keyStore->isValidArrayTableKey($fullTableName)) {
            $this->syntaxError(sprintf('The key "%s" has already been defined previously.', $fullTableName));
        }

        if ($this->keyStore->isTableImplicitFromArryTable($fullTableName)) {
            $this->syntaxError(sprintf('The array of tables "%s" has already been defined as previous table', $fullTableName));
        }

        $this->keyStore->addArrayTableKey($fullTableName);
        $this->tomlArray->addArrayTableKey($fullTableName);

        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);
        $this->matchNext('T_RIGHT_SQUARE_BRAKET', $ts);

        $this->parseSpaceIfExists($ts);
        $this->parseCommentIfExists($ts);
        $this->errorIfNextIsNotNewlineOrEOS($ts);
    }

    private function matchNext(string $tokenName, TokenStream $ts): string
    {
        if (!$ts->isNext($tokenName)) {
            $this->unexpectedTokenError($ts->moveNext(), sprintf('Expected "%s".', $tokenName));
        }

        $token = $ts->moveNext();
        assert($token instanceof Token);

        return $token->getValue();
    }

    private function parseSpaceIfExists(TokenStream $ts): void
    {
        if (!$ts->isNext('T_SPACE')) {
            return;
        }

        $ts->moveNext();
    }

    private function parseCommentIfExists(TokenStream $ts): void
    {
        if (!$ts->isNext('T_HASH')) {
            return;
        }

        $this->parseComment($ts);
    }

    private function parseCommentsInsideBlockIfExists(TokenStream $ts): void
    {
        $this->parseCommentIfExists($ts);

        while ($ts->isNext('T_NEWLINE')) {
            $ts->moveNext();
            $ts->skipWhile('T_SPACE');
            $this->parseCommentIfExists($ts);
        }
    }

    private function errorIfNextIsNotNewlineOrEOS(TokenStream $ts): void
    {
        if ($ts->isNextAny(['T_NEWLINE', 'T_EOS'])) {
            return;
        }

        $this->unexpectedTokenError($ts->moveNext(), 'Expected T_NEWLINE or T_EOS.');
    }

    /**
     * Throw an exception for unexpected token errors.
     *
     * Creates a detailed error message including token information and line numbers
     * to help diagnose parsing failures. Used when the parser encounters tokens
     * that don't match expected syntax patterns.
     *
     * @param null|Token $token       Token that caused the error, or null if at end of stream
     * @param string     $expectedMsg Description of what was expected at this position
     *
     * @throws ParserUnexpectedTokenException Always thrown with context about the error
     * @return never
     */
    private function unexpectedTokenError(?Token $token, string $expectedMsg): void
    {
        if ($token instanceof Token) {
            throw ParserUnexpectedTokenException::forToken($token, $expectedMsg);
        }

        throw ParserUnexpectedTokenException::forNullToken($expectedMsg);
    }

    /**
     * Throw an exception for TOML syntax violations.
     *
     * Used for semantic errors like duplicate keys, invalid table hierarchies,
     * or data type constraint violations that don't involve unexpected tokens.
     *
     * @param string     $msg   Description of the syntax error
     * @param null|Token $token Optional token providing line number context
     *
     * @throws ParserSyntaxErrorException Always thrown with error details
     */
    private function syntaxError(string $msg, ?Token $token = null): never
    {
        throw ParserSyntaxErrorException::withMessage($msg, $token);
    }
}
