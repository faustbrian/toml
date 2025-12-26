<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Lexer;

use Cline\Toml\Exception\UnexpectedTokenException;

use function array_any;
use function count;

/**
 * Provides sequential access to a collection of lexical tokens during TOML parsing.
 *
 * TokenStream enables lookahead operations and pattern matching on token sequences
 * without consuming the underlying token array. It maintains an internal pointer
 * for tracking position and supports operations like peeking ahead, matching
 * specific tokens, and skipping token patterns.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TokenStream
{
    /**
     * Current position in the token stream, starting at -1 before first token.
     */
    private int $index = -1;

    /**
     * Create a new token stream.
     *
     * @param array<int, Token> $tokens Collection of lexical tokens to stream through.
     *                                  Tokens are accessed sequentially using moveNext()
     *                                  and lookahead methods without modifying the array.
     */
    public function __construct(
        private readonly array $tokens,
    ) {}

    /**
     * Advance to the next token in the stream and return it.
     *
     * Increments the internal pointer and returns the token at the new position.
     * Returns null when reaching the end of the stream.
     *
     * @return null|Token The next token, or null if no more tokens are available
     */
    public function moveNext(): ?Token
    {
        return $this->tokens[++$this->index] ?? null;
    }

    /**
     * Assert that the next token matches the expected type and return its value.
     *
     * Performs a lookahead check without consuming the token, then advances
     * and returns the token value if it matches. This method ensures the
     * parser encounters expected token sequences during TOML syntax validation.
     *
     * @param string $tokenName Expected token type (e.g., 'T_EQUAL', 'T_QUOTATION_MARK')
     *
     * @throws UnexpectedTokenException When the next token type doesn't match the expected type
     * @return string                   The value of the matched token
     */
    public function matchNext(string $tokenName): string
    {
        $token = $this->moveNext();
        --$this->index;

        if ($token instanceof Token && $token->getName() === $tokenName) {
            /** @var Token $matched Always a Token since we just verified */
            $matched = $this->moveNext();

            return $matched->getValue();
        }

        throw UnexpectedTokenException::forToken($tokenName, $token?->getName() ?? 'null', $token?->getLine() ?? 0);
    }

    /**
     * Skip consecutive tokens of a specific type.
     *
     * Advances the stream pointer past all consecutive tokens matching the given type.
     * Useful for consuming whitespace, newlines, or other repeated token patterns.
     *
     * @param string $tokenName Token type to skip (e.g., 'T_SPACE', 'T_NEWLINE')
     */
    public function skipWhile(string $tokenName): void
    {
        $this->skipWhileAny([$tokenName]);
    }

    /**
     * Skip consecutive tokens matching any of the specified types.
     *
     * Advances the stream pointer past all consecutive tokens whose type matches
     * any value in the provided array. Commonly used to skip combinations of
     * whitespace, newlines, and other formatting tokens.
     *
     * @param array<int, string> $tokenNames Array of token types to skip (e.g., ['T_SPACE', 'T_NEWLINE'])
     */
    public function skipWhileAny(array $tokenNames): void
    {
        while ($this->isNextAny($tokenNames)) {
            $this->moveNext();
        }
    }

    /**
     * Check if the next token matches a specific type without consuming it.
     *
     * Performs lookahead by advancing, checking the token type, then restoring
     * the original position. This enables the parser to make decisions based on
     * upcoming tokens without altering the stream state.
     *
     * @param string $tokenName Token type to check for (e.g., 'T_HASH', 'T_EQUAL')
     *
     * @return bool True if the next token matches the specified type, false otherwise
     */
    public function isNext(string $tokenName): bool
    {
        $token = $this->moveNext();
        --$this->index;

        if (!$token instanceof Token) {
            return false;
        }

        return $token->getName() === $tokenName;
    }

    /**
     * Check if upcoming tokens match an exact sequence without consuming them.
     *
     * Performs lookahead across multiple tokens to verify a specific pattern.
     * The stream position is restored after checking. Used to detect complex
     * TOML constructs like array of tables ([[...]]) before parsing them.
     *
     * @param array<int, string> $tokenNames Ordered array of token types to match
     *                                       (e.g., ['T_LEFT_SQUARE_BRAKET', 'T_LEFT_SQUARE_BRAKET'])
     *
     * @return bool True if the next tokens match the sequence exactly, false otherwise
     */
    public function isNextSequence(array $tokenNames): bool
    {
        $result = true;
        $currentIndex = $this->index;

        foreach ($tokenNames as $tokenName) {
            $token = $this->moveNext();

            if (!$token instanceof Token || $token->getName() !== $tokenName) {
                $result = false;

                break;
            }
        }

        $this->index = $currentIndex;

        return $result;
    }

    /**
     * Check if the next token matches any type from a set without consuming it.
     *
     * Performs lookahead to test if the next token belongs to a specific group.
     * Useful for handling multiple valid token types at a given parsing position,
     * such as checking for any string delimiter or numeric type.
     *
     * @param array<int, string> $tokenNames Array of token types to check against
     *                                       (e.g., ['T_QUOTATION_MARK', 'T_APOSTROPHE'])
     *
     * @return bool True if the next token matches any of the specified types, false otherwise
     */
    public function isNextAny(array $tokenNames): bool
    {
        $token = $this->moveNext();
        --$this->index;

        if (!$token instanceof Token) {
            return false;
        }

        return array_any($tokenNames, fn ($tokenName): bool => $tokenName === $token->getName());
    }

    /**
     * Retrieve the complete collection of tokens in this stream.
     *
     * Returns the underlying token array without modification. Useful for
     * debugging, analysis, or operations that need access to all tokens
     * regardless of the current stream position.
     *
     * @return array<int, Token> Complete array of tokens in the stream
     */
    public function getAll(): array
    {
        return $this->tokens;
    }

    /**
     * Determine if unprocessed tokens remain in the stream.
     *
     * Checks whether the current position has advanced through all tokens.
     * Used to control parsing loops that process the entire token sequence
     * until reaching the end of the stream.
     *
     * @return bool True if tokens remain to be processed, false if at or past the end
     */
    public function hasPendingTokens(): bool
    {
        $tokenCount = count($this->tokens);

        if ($tokenCount === 0) {
            return false;
        }

        return $this->index < ($tokenCount - 1);
    }

    /**
     * Reset the stream position to the beginning.
     *
     * Rewinds the internal pointer to position -1, allowing the stream to be
     * processed again from the start. Useful for multi-pass parsing or when
     * re-parsing the same token sequence is needed.
     */
    public function reset(): void
    {
        $this->index = -1;
    }
}
