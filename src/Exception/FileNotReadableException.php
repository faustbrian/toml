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
 * Exception thrown when a TOML file cannot be read.
 */
final class FileNotReadableException extends ParseException
{
    public static function forPath(string $filename): self
    {
        return new self(sprintf('File "%s" cannot be read.', $filename));
    }
}
