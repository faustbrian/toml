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
 * Exception thrown when a TOML string contains invalid or disallowed characters.
 *
 * TOML strings have specific character requirements and restrictions depending on
 * the string type (basic, literal, multi-line). This exception is thrown during
 * TOML dumping when a string value contains characters that cannot be represented
 * in the TOML format or require special encoding that is not available.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidStringCharactersException extends DumpException
{
    /**
     * Creates an exception for a string with invalid characters at a specific key.
     *
     * @param  string $key The key path where the invalid string was encountered
     * @return self   The exception instance with formatted error message
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('The string has an invalid charters at the key "%s".', $key));
    }
}
