<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml\Exception;

use RuntimeException;

/**
 * Base exception for all TOML dumping and serialization errors.
 *
 * This abstract exception serves as the parent class for all exceptions thrown
 * during the process of converting PHP data structures to TOML format. Specific
 * dump-related errors extend this class to provide more detailed context about
 * the nature of the serialization failure.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class DumpException extends RuntimeException implements TomlException {}
