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
 * Exception thrown when an unsupported data type is used in an array.
 */
final class UnsupportedArrayTypeException extends DumpException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Unsupported data type in array at key: "%s".', $key));
    }
}
