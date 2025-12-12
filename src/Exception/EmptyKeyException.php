<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

/**
 * Exception thrown when an empty key, table name, or array table name is encountered.
 *
 * TOML requires all keys and table identifiers to be non-empty. This exception
 * is raised during serialization when the dumper encounters an empty or null
 * value for a key name, table name, or array table name.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EmptyKeyException extends DumpException
{
    /**
     * Creates an exception for an empty key or table name.
     *
     * @param string $additionalMessage Optional context to append to the error message
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function create(string $additionalMessage = ''): self
    {
        $message = 'A key, table name or array of table name cannot be empty or null.';

        if ($additionalMessage !== '') {
            $message .= ' '.$additionalMessage;
        }

        return new self($message);
    }
}
