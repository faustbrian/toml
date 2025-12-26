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
 * Exception thrown when a general syntax error is encountered during parsing.
 *
 * This exception represents various syntax errors that occur during the parsing
 * phase after lexical analysis. It can include detailed information about the
 * problematic token including its name, line number, and value. Used for syntax
 * errors that don't fall into more specific exception categories.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ParserSyntaxErrorException extends SyntaxErrorException
{
    /**
     * Creates a syntax error exception with an optional token for context.
     *
     * When a token is provided, its details (name, line number, and value) are
     * automatically appended to the error message to provide detailed debugging
     * information about the location and nature of the syntax error.
     *
     * @param  string     $msg   The error message describing the syntax error
     * @param  null|Token $token The token where the syntax error occurred, or null for general errors
     * @return self       The exception instance with formatted message and optional token context
     */
    public static function withMessage(string $msg, ?Token $token = null): self
    {
        if ($token instanceof Token) {
            $name = $token->getName();
            $line = $token->getLine();
            $value = $token->getValue();
            $tokenMsg = sprintf('Token: "%s" line: %s value "%s".', $name, $line, $value);
            $msg .= ' '.$tokenMsg;
        }

        $exception = new self($msg);

        if ($token instanceof Token) {
            $exception->setToken($token);
        }

        return $exception;
    }
}
