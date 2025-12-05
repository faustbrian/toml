<?php

declare(strict_types=1);

/*
 * This file is part of the Cline\Toml package.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use function sprintf;

/**
 * Exception thrown when a key has been defined as an implicit table from a previous array of tables.
 */
final class ImplicitTableFromArrayOfTablesException extends DumpException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('The key "%s" has been defined as a implicit table from a previous array of tables.', $key));
    }
}
