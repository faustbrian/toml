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
 * Exception thrown when attempting to define a duplicate table key in TOML output.
 *
 * In TOML, table names (defined with [table.name]) must be unique. This exception
 * is raised during serialization when the dumper detects an attempt to redefine
 * a table that has already been declared in the document.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DuplicateTableKeyException extends DumpException
{
    /**
     * Creates an exception for a duplicate table key.
     *
     * @param string $key The table key that was already defined
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('The table key "%s" has already been defined previously.', $key));
    }
}
