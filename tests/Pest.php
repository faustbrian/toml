<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\KeyStore;
use Cline\Toml\Lexer;
use Cline\Toml\Parser;
use Cline\Toml\TomlArray;
use Cline\Toml\TomlBuilder;

pest()->in(__DIR__);

/**
 * Creates a new parser instance.
 */
function createParser(): Parser
{
    return new Parser(
        new Lexer(),
    );
}

/**
 * Creates a new lexer instance.
 */
function createLexer(): Lexer
{
    return new Lexer();
}

/**
 * Creates a new key store instance.
 */
function createKeyStore(): KeyStore
{
    return new KeyStore();
}

/**
 * Creates a new TOML array instance.
 */
function createTomlArray(): TomlArray
{
    return new TomlArray();
}

/**
 * Creates a new TOML builder instance.
 */
function createTomlBuilder(int $indent = 4): TomlBuilder
{
    return new TomlBuilder($indent);
}
