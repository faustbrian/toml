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
 * Exception thrown when attempting to redefine a table that already exists as an array of tables.
 *
 * In TOML, once a table is defined as an array of tables using double brackets [[table]],
 * it cannot be redefined as a standard table. This exception signals such conflicts during
 * the dump/serialization process.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TableAlreadyDefinedAsArrayException extends DumpException
{
    /**
     * Creates an exception for a table key that conflicts with an existing array of tables.
     *
     * @param string $key The table key that has already been defined as an array of tables
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('The table "%s" has already been defined as previous array of tables.', $key));
    }
}
