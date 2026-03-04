<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use Cline\Toml\Lexer\Token;
use RuntimeException;

/**
 * Base exception for all syntax errors encountered during TOML parsing.
 *
 * This abstract class serves as the foundation for all syntax-related exceptions
 * thrown during the lexical analysis and parsing phases. It provides token context
 * to help identify the exact location and nature of syntax violations in the TOML input.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class SyntaxErrorException extends RuntimeException implements TomlException
{
    /**
     * The token associated with this syntax error, if available.
     */
    private ?Token $token = null;

    /**
     * Associates a token with this exception for error context.
     *
     * @param Token $token The token where the syntax error occurred
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }

    /**
     * Retrieves the token associated with this syntax error.
     *
     * @return null|Token The token where the error occurred, or null if not set
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }
}
