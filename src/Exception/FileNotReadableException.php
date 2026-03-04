<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a TOML file exists but cannot be read.
 *
 * This exception is raised when attempting to parse or load a TOML file that
 * exists in the filesystem but lacks read permissions or is otherwise inaccessible.
 * Common causes include insufficient file permissions or file locks.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class FileNotReadableException extends RuntimeException implements TomlException
{
    /**
     * Creates an exception for an unreadable file.
     *
     * @param string $filename The path to the file that cannot be read
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forFile(string $filename): self
    {
        return new self(sprintf('File "%s" cannot be read.', $filename));
    }
}
