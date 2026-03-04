<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Lexer;

use Cline\Toml\Exception\LexerParseException;

/**
 * Contract for lexical analyzers that transform raw input into token streams.
 *
 * Lexers implementing this interface perform lexical analysis (tokenization) by scanning
 * input text and breaking it into a sequence of tokens based on defined grammar rules.
 * The resulting token stream can then be consumed by a parser to build an abstract syntax tree.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface LexerInterface
{
    /**
     * Performs lexical analysis on the input string and produces a token stream.
     *
     * @param string $input The raw input text to tokenize
     *
     * @throws LexerParseException If the input contains unrecognized characters
     * @return TokenStream         A sequential stream of tokens extracted from the input
     */
    public function tokenize(string $input): TokenStream;
}
