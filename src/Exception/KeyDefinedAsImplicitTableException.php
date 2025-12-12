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
 * Exception thrown when attempting to redefine a key that exists as an implicit table.
 *
 * In TOML, when an array of tables is defined, it implicitly creates parent table
 * structures. If a subsequent operation attempts to explicitly define a key that was
 * already created implicitly as part of an array of tables hierarchy, this exception
 * is thrown during TOML dumping to prevent conflicting table definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class KeyDefinedAsImplicitTableException extends DumpException
{
    /**
     * Creates an exception for a key that conflicts with an implicit table.
     *
     * @param  string $key The key path that was already defined as an implicit table
     * @return self   The exception instance with formatted error message
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('The key "%s" has been defined as a implicit table from a previous array of tables.', $key));
    }
}
