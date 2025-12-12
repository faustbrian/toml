<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use LogicException;

use function sprintf;

/**
 * Exception thrown when a TOML key does not conform to the TOML specification.
 *
 * Keys in TOML must follow specific naming rules including valid character sets
 * and proper quoting. This exception is thrown during parsing when a key violates
 * these requirements, such as containing illegal characters or improper formatting.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidKeyException extends LogicException implements TomlException
{
    /**
     * Creates an exception for an invalid key name.
     *
     * @param  string $name The invalid key name that caused the exception
     * @return self   The exception instance with formatted error message
     */
    public static function forKey(string $name): self
    {
        return new self(sprintf('The key "%s" is not valid.', $name));
    }
}
