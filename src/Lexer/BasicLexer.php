<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Lexer;

use Cline\Toml\Exception\EmptyEosTokenNameException;
use Cline\Toml\Exception\EmptyNewlineTokenNameException;
use Cline\Toml\Exception\LexerParseException;

use function count;
use function explode;
use function mb_strlen;
use function mb_substr;
use function preg_match;

/**
 * Regex-based lexical analyzer that tokenizes TOML input into a stream of tokens.
 *
 * This lexer scans input text line-by-line and matches patterns against configured
 * terminal symbols (regex patterns mapped to token names). It supports optional
 * generation of newline and end-of-string tokens for grammar rules that require them.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class BasicLexer implements LexerInterface
{
    /**
     * Token name for newline characters when newline token generation is enabled.
     */
    private string $newlineTokenName = 'T_NEWLINE';

    /**
     * Token name for end-of-string marker when EOS token generation is enabled.
     */
    private string $eosTokenName = 'T_EOS';

    /**
     * Whether to generate explicit tokens for newline characters.
     */
    private bool $activateNewlineToken = false;

    /**
     * Whether to generate an end-of-string token after all input is processed.
     */
    private bool $activateEOSToken = false;

    /**
     * Creates a lexer with the specified terminal symbol definitions.
     *
     * @param array<string, string> $terminals Map of regex patterns to token names that define
     *                                         the terminal symbols recognized by this lexer. Each
     *                                         pattern should capture the token value in group 1.
     */
    public function __construct(
        private readonly array $terminals,
    ) {}

    /**
     * Enables explicit newline token generation for line-aware grammars.
     *
     * @return static Fluent interface for method chaining
     */
    public function generateNewlineTokens(): static
    {
        $this->activateNewlineToken = true;

        return $this;
    }

    /**
     * Enables end-of-string token generation to signal input termination.
     *
     * @return static Fluent interface for method chaining
     */
    public function generateEosToken(): static
    {
        $this->activateEOSToken = true;

        return $this;
    }

    /**
     * Configures a custom token name for newline tokens.
     *
     * @param string $name The token name to use for newlines
     *
     * @throws EmptyNewlineTokenNameException If the provided name is empty
     * @return static                         Fluent interface for method chaining
     */
    public function setNewlineTokenName(string $name): static
    {
        if ($name === '') {
            throw EmptyNewlineTokenNameException::create();
        }

        $this->newlineTokenName = $name;

        return $this;
    }

    /**
     * Configures a custom token name for the end-of-string marker.
     *
     * @param string $name The token name to use for end-of-string
     *
     * @throws EmptyEosTokenNameException If the provided name is empty
     * @return static                     Fluent interface for method chaining
     */
    public function setEosTokenName(string $name): static
    {
        if ($name === '') {
            throw EmptyEosTokenNameException::create();
        }

        $this->eosTokenName = $name;

        return $this;
    }

    /**
     * Tokenizes the input string into a stream of tokens.
     *
     * Processes the input line-by-line, matching terminal patterns from left to right
     * at each position. Optionally generates newline tokens between lines and an
     * end-of-string token after all input is consumed.
     *
     * @param string $input The TOML content to tokenize
     *
     * @throws LexerParseException If no terminal pattern matches at any position
     * @return TokenStream         A stream of tokens ready for parsing
     */
    public function tokenize(string $input): TokenStream
    {
        $counter = 0;
        $tokens = [];
        $lines = explode("\n", $input);
        $totalLines = count($lines);
        $lineNumber = 1;

        foreach ($lines as $number => $line) {
            $offset = 0;
            $lineNumber = $number + 1;

            while ($offset < mb_strlen($line)) {
                [$name, $matches] = $this->match($line, $lineNumber, $offset);

                if (isset($matches[1])) {
                    $token = new Token($matches[1], $name, $lineNumber);
                    $this->processToken();
                    $tokens[] = $token;
                }

                $offset += mb_strlen($matches[0]);
            }

            if (!$this->activateNewlineToken) {
                continue;
            }

            if (++$counter >= $totalLines) {
                continue;
            }

            $tokens[] = new Token("\n", $this->newlineTokenName, $lineNumber);
        }

        if ($this->activateEOSToken) {
            $tokens[] = new Token('', $this->eosTokenName, $lineNumber);
        }

        return new TokenStream($tokens);
    }

    /**
     * Matches terminal patterns against the current line position.
     *
     * Iterates through the configured terminal patterns in order, returning the first
     * match found. This determines which type of token should be created for the current
     * position in the input.
     *
     * @param string $line       The current line being tokenized
     * @param int    $lineNumber The line number for error reporting
     * @param int    $offset     The character offset within the line
     *
     * @throws LexerParseException                            If no terminal pattern matches the current position
     * @return array{0: string, 1: array<int|string, string>} Tuple of [token name, regex matches]
     */
    private function match(string $line, int $lineNumber, int $offset): array
    {
        $restLine = mb_substr($line, $offset);

        foreach ($this->terminals as $pattern => $name) {
            if (preg_match($pattern, $restLine, $matches)) {
                return [
                    $name,
                    $matches,
                ];
            }
        }

        throw LexerParseException::forLine($line, $lineNumber);
    }

    /**
     * Post-processes a token after creation.
     *
     * This hook allows subclasses to apply additional transformations or validations
     * to tokens based on the matched regex groups. The base implementation performs
     * no processing.
     */
    private function processToken(): void
    {
        // Override in subclasses if needed
    }
}
