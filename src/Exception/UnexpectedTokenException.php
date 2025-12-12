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
 * Exception thrown when the parser encounters a token that doesn't match the expected type.
 *
 * This exception occurs during parsing when the lexer produces a token that doesn't
 * align with the grammar rules for the current parsing context. It provides detailed
 * information about both the expected and actual token types, along with the line number
 * where the mismatch occurred.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedTokenException extends SyntaxErrorException
{
    /**
     * Creates an exception for a token type mismatch.
     *
     * @param string $expectedType The token type that was expected by the parser
     * @param string $tokenType    The actual token type that was encountered
     * @param int    $line         The line number where the unexpected token occurred
     */
    public static function forToken(string $expectedType, string $tokenType, int $line): self
    {
        return new self(sprintf(
            'Syntax error: expected token with name "%s" instead of "%s" at line %s.',
            $expectedType,
            $tokenType,
            $line,
        ));
    }
}
