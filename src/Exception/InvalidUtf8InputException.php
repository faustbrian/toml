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

use Yosymfony\ParserUtils\SyntaxErrorException;

/**
 * Exception thrown when TOML input is not valid UTF-8.
 */
final class InvalidUtf8InputException extends SyntaxErrorException implements TomlException
{
    public static function create(): self
    {
        return new self('The TOML input does not appear to be valid UTF-8.');
    }
}
