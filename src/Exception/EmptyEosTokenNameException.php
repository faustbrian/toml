<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use InvalidArgumentException;

/**
 * Exception thrown when an empty End-of-Stream (EOS) token name is provided.
 *
 * The TOML lexer requires a non-empty name for the EOS token to properly
 * identify the end of input during tokenization. This exception is raised
 * when attempting to configure the lexer with an empty or null EOS token name.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EmptyEosTokenNameException extends InvalidArgumentException implements TomlException
{
    /**
     * Creates an exception for an empty EOS token name.
     *
     * @return self The exception instance with a descriptive error message
     */
    public static function create(): self
    {
        return new self('The name of the EOS token must be not empty.');
    }
}
