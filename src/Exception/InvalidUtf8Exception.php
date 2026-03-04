<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

/**
 * Exception thrown when TOML input contains invalid UTF-8 byte sequences.
 *
 * The TOML specification requires all input to be valid UTF-8 encoded text.
 * This exception is thrown during parsing when the input contains malformed
 * UTF-8 sequences, invalid byte combinations, or non-UTF-8 encoded content.
 * All TOML files must use UTF-8 encoding without a byte order mark (BOM).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidUtf8Exception extends SyntaxErrorException
{
    /**
     * Creates an exception for invalid UTF-8 input.
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function create(): self
    {
        return new self('The TOML input does not appear to be valid UTF-8.');
    }
}
