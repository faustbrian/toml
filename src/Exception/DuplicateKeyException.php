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
 * Exception thrown when attempting to define a duplicate key in TOML output.
 *
 * TOML requires all keys within a table to be unique. This exception is raised
 * during serialization when the dumper encounters an attempt to assign a value
 * to a key that has already been defined in the current table scope.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DuplicateKeyException extends DumpException
{
    /**
     * Creates an exception for a duplicate key.
     *
     * @param string $key The key name that was already defined
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('The key "%s" has already been defined previously.', $key));
    }
}
