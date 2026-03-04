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
 * Exception thrown when a TOML file does not exist at the specified path.
 *
 * This exception is raised when attempting to parse or load a TOML file that
 * cannot be found in the filesystem. It typically occurs when an invalid file
 * path is provided or the file has been moved or deleted.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class FileNotFoundException extends RuntimeException implements TomlException
{
    /**
     * Creates an exception for a non-existent file.
     *
     * @param string $filename The path to the file that was not found
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forFile(string $filename): self
    {
        return new self(sprintf('File "%s" does not exist.', $filename));
    }
}
