<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use Throwable;

/**
 * Marker interface for all TOML package exceptions.
 *
 * This interface serves as the common contract for all exceptions thrown by the TOML parser
 * library. Consumers can catch this interface to handle any exception originating from TOML
 * parsing, lexing, or dumping operations without needing to catch individual exception types.
 *
 * ```php
 * try {
 *     $parser->parse($tomlContent);
 * } catch (TomlException $e) {
 *     // Handle any TOML-related error
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface TomlException extends Throwable
{
    // Marker interface - no methods required
}
