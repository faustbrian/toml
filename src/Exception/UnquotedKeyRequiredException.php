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
 * Exception thrown when a quoted key is provided but only unquoted keys are allowed.
 *
 * This exception occurs during TOML dumping when the implementation requires bare/unquoted
 * keys but encounters a key that requires quoting (e.g., contains special characters or spaces).
 * TOML keys can be bare (unquoted), quoted, or dotted; this exception enforces the constraint
 * that only bare keys are acceptable in certain contexts.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnquotedKeyRequiredException extends DumpException
{
    /**
     * Creates an exception for a key that requires quoting when only unquoted keys are allowed.
     *
     * @param string $key The key that violates the unquoted-only constraint
     */
    public static function forKey(string $key): self
    {
        return new self(sprintf('Only unquoted keys are allowed in this implementation. Key: "%s".', $key));
    }
}
