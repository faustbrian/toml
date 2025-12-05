<?php

declare(strict_types=1);

/*
 * This file is part of the Cline Toml package.
 *
 * (c) Cline <https://cline.sh>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\Lexer;
use Cline\Toml\Parser;

function createParser(): Parser
{
    return new Parser(new Lexer());
}
