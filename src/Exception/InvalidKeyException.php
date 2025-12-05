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

use LogicException;

use function sprintf;

/**
 * Exception thrown when a TOML key is invalid.
 */
final class InvalidKeyException extends LogicException implements TomlException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('The key "%s" is not valid.', $name));
    }
}
