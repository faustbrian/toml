<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use Cline\Toml\Lexer\BasicLexer;
use Cline\Toml\Lexer\LexerInterface;
use Cline\Toml\Lexer\TokenStream;

/**
 * Tokenizes TOML input strings into a stream of lexical tokens.
 *
 * The Lexer breaks down TOML syntax into recognizable tokens using regex patterns
 * for all TOML data types and structural elements (strings, numbers, dates, brackets,
 * operators). It handles both basic and literal string formats, numeric underscores,
 * escape sequences, and all TOML structural characters defined in the specification.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Lexer implements LexerInterface
{
    /**
     * Internal lexer engine that performs pattern matching and token generation.
     */
    private BasicLexer $basicLexer;

    /**
     * Create a new TOML lexer with configured token patterns.
     *
     * Initializes the lexer with regex patterns for all TOML token types including
     * literals, strings, numbers, dates, structural characters, and escape sequences.
     * Enables automatic generation of newline and end-of-stream tokens for parsing.
     */
    public function __construct()
    {
        $this->basicLexer = new BasicLexer([
            '/^(=)/' => 'T_EQUAL',
            '/^(true|false)/' => 'T_BOOLEAN',
            '/^(\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\.\d{6})?(Z|-\d{2}:\d{2})?)?)/' => 'T_DATE_TIME',
            '/^([+-]?((((\d_?)+[\.]?(\d_?)*)[eE][+-]?(\d_?)+)|((\d_?)+[\.](\d_?)+)))/' => 'T_FLOAT',
            '/^([+-]?(\d_?)+)/' => 'T_INTEGER',
            '/^(""")/' => 'T_3_QUOTATION_MARK',
            '/^(")/' => 'T_QUOTATION_MARK',
            "/^(''')/" => 'T_3_APOSTROPHE',
            "/^(')/" => 'T_APOSTROPHE',
            '/^(#)/' => 'T_HASH',
            '/^(\s+)/' => 'T_SPACE',
            '/^(\[)/' => 'T_LEFT_SQUARE_BRAKET',
            '/^(\])/' => 'T_RIGHT_SQUARE_BRAKET',
            '/^(\{)/' => 'T_LEFT_CURLY_BRACE',
            '/^(\})/' => 'T_RIGHT_CURLY_BRACE',
            '/^(,)/' => 'T_COMMA',
            '/^(\.)/' => 'T_DOT',
            '/^([-A-Z_a-z0-9]+)/' => 'T_UNQUOTED_KEY',
            '/^(\\\(b|t|n|f|r|"|\\\\|u[0-9AaBbCcDdEeFf]{4,4}|U[0-9AaBbCcDdEeFf]{8,8}))/' => 'T_ESCAPED_CHARACTER',
            '/^(\\\)/' => 'T_ESCAPE',
            '/^([\x{08}-\x{0D}\x{20}-\x{21}\x{23}-\x{26}\x{28}-\x{5A}\x{5E}-\x{10FFFF}]+)/u' => 'T_BASIC_UNESCAPED',
        ]);

        $this->basicLexer
            ->generateNewlineTokens()
            ->generateEosToken();
    }

    /**
     * Convert TOML input string into a stream of tokens.
     *
     * Scans the input and generates tokens for all recognized patterns including
     * values, operators, delimiters, and whitespace. The resulting TokenStream
     * provides sequential access for the parser.
     *
     * @param string $input Raw TOML content to tokenize
     *
     * @return TokenStream Stream of lexical tokens ready for parsing
     */
    public function tokenize(string $input): TokenStream
    {
        return $this->basicLexer->tokenize($input);
    }
}
