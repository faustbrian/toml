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
 * Exception thrown when a TOML table key does not conform to the specification.
 *
 * Table keys in TOML (both standard tables and array of tables) must follow
 * specific naming conventions including valid character sets, proper dotted key
 * syntax, and quoting rules. This exception is thrown during parsing when a table
 * header contains an invalid key format or violates TOML table naming requirements.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidTableKeyException extends LogicException implements TomlException
{
    /**
     * Creates an exception for an invalid table key name.
     *
     * @param  string $name The invalid table key that caused the exception
     * @return self   The exception instance with formatted error message
     */
    public static function forKey(string $name): self
    {
        return new self(sprintf('The table key "%s" is not valid.', $name));
    }
}
