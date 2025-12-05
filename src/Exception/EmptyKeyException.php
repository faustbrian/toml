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

/**
 * Exception thrown when a key, table name, or array of table name is empty.
 */
final class EmptyKeyException extends DumpException
{
    public static function create(string $additionalMessage = ''): self
    {
        $message = 'A key, table name or array of table name cannot be empty or null.';

        if ($additionalMessage !== '') {
            $message .= " {$additionalMessage}";
        }

        return new self($message);
    }
}
