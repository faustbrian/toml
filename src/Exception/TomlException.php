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

use Throwable;

/**
 * Marker interface for all Toml package exceptions.
 *
 * Consumers can catch this interface to handle any exception
 * thrown by the Toml package.
 */
interface TomlException extends Throwable
{
    // Marker interface - no methods required
}
