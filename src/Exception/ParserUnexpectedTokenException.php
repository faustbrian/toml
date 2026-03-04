<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use Cline\Toml\Lexer\Token;

use function sprintf;

/**
 * Exception thrown when the parser encounters an unexpected token.
 *
 * This exception is raised when the parser receives a token that is not valid
 * in the current parsing context. For example, encountering a closing bracket
 * when expecting a key-value pair, or finding an equals sign in an invalid
 * position. The exception includes detailed token information to help identify
 * the exact location and nature of the unexpected syntax.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ParserUnexpectedTokenException extends SyntaxErrorException
{
    /**
     * Creates an exception for an unexpected token with optional expectation context.
     *
     * Constructs a detailed error message including the token's name, line number,
     * and value. An optional message can be provided to explain what was expected
     * instead, helping developers understand how to fix the syntax error.
     *
     * @param  Token  $token       The unexpected token that was encountered
     * @param  string $expectedMsg Optional message describing what was expected instead
     * @return self   The exception instance with formatted message and token context
     */
    public static function forToken(Token $token, string $expectedMsg = ''): self
    {
        $name = $token->getName();
        $line = $token->getLine();
        $value = $token->getValue();
        $msg = sprintf('Syntax error: unexpected token "%s" at line %s with value "%s".', $name, $line, $value);

        if ($expectedMsg !== '') {
            $msg = $msg.' '.$expectedMsg;
        }

        $exception = new self($msg);
        $exception->setToken($token);

        return $exception;
    }

    /**
     * Creates an exception when no token exists but one was expected.
     *
     * Used when the parser reaches the end of input or encounters a null token
     * when it expected additional content. This typically occurs with incomplete
     * TOML documents that end prematurely.
     *
     * @param  string $expectedMsg Optional message describing what token was expected
     * @return self   The exception instance indicating missing or null token
     */
    public static function forNullToken(string $expectedMsg = ''): self
    {
        $msg = 'Syntax error: unexpected token "" at line 0 with value "".';

        if ($expectedMsg !== '') {
            $msg = $msg.' '.$expectedMsg;
        }

        return new self($msg);
    }
}
