<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Lexer;

/**
 * Represents a single lexical token extracted from TOML input.
 *
 * Each token contains the matched text value, a type identifier (name), and positional
 * information for error reporting and source mapping. Tokens are immutable value objects
 * created by the lexer during tokenization.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Token
{
    /**
     * Creates a new token with the specified attributes.
     *
     * @param string $value The matched text content of this token
     * @param string $name  The token type identifier (e.g., 'T_STRING', 'T_NUMBER')
     * @param int    $line  The line number where this token was found (1-based)
     */
    public function __construct(
        private string $value,
        private string $name,
        private int $line,
    ) {}

    /**
     * Retrieves the matched text content of this token.
     *
     * @return string The raw text value that was matched by the lexer
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Retrieves the type identifier for this token.
     *
     * @return string The token type name (e.g., 'T_STRING', 'T_NUMBER', 'T_BOOLEAN')
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the source line number where this token was found.
     *
     * @return int The 1-based line number for error reporting and debugging
     */
    public function getLine(): int
    {
        return $this->line;
    }
}
