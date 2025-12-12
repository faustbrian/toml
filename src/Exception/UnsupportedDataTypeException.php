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
 * Exception thrown when attempting to dump a PHP data type that has no TOML equivalent.
 *
 * TOML supports a limited set of data types (strings, integers, floats, booleans, dates,
 * arrays, and tables). This exception occurs when the dumper encounters a PHP value that
 * cannot be represented in TOML format, such as resources, objects without serialization
 * support, or other non-standard types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedDataTypeException extends DumpException
{
    /**
     * Creates an exception for an unsupported data type at a specific key.
     *
     * @param string $key The key where the unsupported data type was encountered
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('Data type not supporter at the key: "%s".', $key));
    }
}
