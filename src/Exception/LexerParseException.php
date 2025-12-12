<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use function sprintf;

/**
 * Exception thrown when the lexer encounters unparseable input during tokenization.
 *
 * The lexer is responsible for breaking down TOML input into a stream of tokens.
 * This exception is thrown when the lexer encounters input that does not match
 * any valid TOML token pattern, indicating malformed syntax that prevents the
 * lexical analysis phase from completing successfully.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LexerParseException extends SyntaxErrorException
{
    /**
     * Creates an exception for a line that the lexer cannot parse.
     *
     * @param  string $line       The content of the line that caused the lexer error
     * @param  int    $lineNumber The line number in the input where the error occurred
     * @return self   The exception instance with formatted error message including context
     */
    public static function forLine(string $line, int $lineNumber): self
    {
        return new self(sprintf('Lexer error: unable to parse "%s" at line %s.', $line, $lineNumber));
    }
}
