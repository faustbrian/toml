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
 * Exception thrown when an array table key violates TOML syntax rules.
 *
 * This exception is raised when the parser or dumper encounters an array table
 * key that does not conform to TOML specifications. Invalid array table keys
 * may contain illegal characters, use incorrect formatting, or violate naming
 * conventions required by the TOML standard.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidArrayTableKeyException extends LogicException implements TomlException
{
    /**
     * Creates an exception for an invalid array table key.
     *
     * @param string $name The invalid array table key name
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forKey(string $name): self
    {
        return new self(sprintf('The array table key "%s" is not valid.', $name));
    }
}
