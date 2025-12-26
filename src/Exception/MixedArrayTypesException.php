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
 * Exception thrown when an array contains mixed data types.
 *
 * TOML requires all elements within an array to be of the same type. Arrays
 * cannot mix strings with integers, tables with primitives, or any other
 * heterogeneous combinations. This exception is thrown during TOML dumping
 * when attempting to serialize an array that violates this type homogeneity
 * requirement of the TOML specification.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MixedArrayTypesException extends DumpException
{
    /**
     * Creates an exception for an array with mixed data types.
     *
     * @param  string $key The key path where the mixed-type array was encountered
     * @return self   The exception instance with formatted error message
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('Data types cannot be mixed in an array. Key: "%s".', $key));
    }
}
