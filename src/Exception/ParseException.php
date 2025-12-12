<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use RuntimeException;
use Throwable;

use const JSON_THROW_ON_ERROR;

use function json_encode;
use function mb_substr;
use function sprintf;
use function str_ends_with;

/**
 * Exception thrown when an error occurs during TOML parsing with rich context.
 *
 * This exception provides detailed error information including the error message,
 * line number, code snippet, and file location. It dynamically formats the error
 * message to include all available context, making it easier to diagnose and fix
 * TOML syntax issues. The implementation is based on Symfony's YAML ParseException.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ParseException extends RuntimeException implements TomlException
{
    /**
     * Creates a new parse exception with contextual information.
     *
     * @param string         $rawMessage The base error message describing what went wrong
     * @param int            $parsedLine The line number where the error occurred, or -1 if unknown
     * @param null|string    $snippet    A snippet of code near the error for context
     * @param null|string    $parsedFile The file path being parsed, or null for string input
     * @param null|Throwable $previous   The previous exception for exception chaining
     */
    public function __construct(
        private readonly string $rawMessage,
        private int $parsedLine = -1,
        private ?string $snippet = null,
        private ?string $parsedFile = null,
        ?Throwable $previous = null,
    ) {
        $this->updateRepr();

        parent::__construct($this->message, 0, $previous);
    }

    /**
     * Retrieves the code snippet near the error location.
     *
     * @return null|string The code snippet showing context around the error, or null if not set
     */
    public function getSnippet(): ?string
    {
        return $this->snippet;
    }

    /**
     * Updates the code snippet and regenerates the formatted error message.
     *
     * @param string $snippet The code snippet to include in error context
     */
    public function setSnippet(string $snippet): void
    {
        $this->snippet = $snippet;

        $this->updateRepr();
    }

    /**
     * Retrieves the filename where the parsing error occurred.
     *
     * Returns null when parsing from a string rather than a file.
     *
     * @return null|string The file path that was being parsed, or null for string input
     */
    public function getParsedFile(): ?string
    {
        return $this->parsedFile;
    }

    /**
     * Updates the parsed file path and regenerates the formatted error message.
     *
     * @param string $parsedFile The file path to include in error context
     */
    public function setParsedFile(string $parsedFile): void
    {
        $this->parsedFile = $parsedFile;

        $this->updateRepr();
    }

    /**
     * Retrieves the line number where the parsing error occurred.
     *
     * @return int The line number, or -1 if the line number is unknown
     */
    public function getParsedLine(): int
    {
        return $this->parsedLine;
    }

    /**
     * Updates the error line number and regenerates the formatted error message.
     *
     * @param int $parsedLine The line number where the error occurred
     */
    public function setParsedLine(int $parsedLine): void
    {
        $this->parsedLine = $parsedLine;

        $this->updateRepr();
    }

    /**
     * Rebuilds the formatted error message with all available context.
     *
     * Combines the raw error message with file location, line number, and code
     * snippet information to create a comprehensive error message. This method
     * is called automatically whenever context information is updated.
     */
    private function updateRepr(): void
    {
        $this->message = $this->rawMessage;

        $dot = false;

        if (str_ends_with($this->message, '.')) {
            $this->message = mb_substr($this->message, 0, -1);
            $dot = true;
        }

        if ($this->parsedFile !== null) {
            $this->message .= sprintf(' in %s', json_encode($this->parsedFile, JSON_THROW_ON_ERROR));
        }

        if ($this->parsedLine >= 0) {
            $this->message .= sprintf(' at line %d', $this->parsedLine);
        }

        if ($this->snippet !== null) {
            $this->message .= sprintf(' (near "%s")', $this->snippet);
        }

        if (!$dot) {
            return;
        }

        $this->message .= '.';
    }
}
