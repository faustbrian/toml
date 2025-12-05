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

use Cline\Toml\Exception\DumpException;
use Cline\Toml\TomlBuilder;

beforeEach(function (): void {
    $this->builder = new TomlBuilder();
});

test('addValue fails when empty key', function (): void {
    $this->builder->addValue('', 'value');
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

test('addTable fails when empty key', function (): void {
    $this->builder->addTable('');
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

test('addArrayOfTable fails when empty key', function (): void {
    $this->builder->addArrayOfTable('');
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

test('addValue fails when key with just whitespaces', function (): void {
    $whiteSpaceKey = ' ';

    $this->builder->addValue($whiteSpaceKey, 'value');
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

test('addTable fails when key with just whitespaces', function (): void {
    $whiteSpaceKey = ' ';

    $this->builder->addTable($whiteSpaceKey);
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

test('addArrayOfTable fails when key with just whitespaces', function (): void {
    $whiteSpaceKey = ' ';

    $this->builder->addArrayOfTable($whiteSpaceKey);
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

test('addValue fails when mixed types', function (): void {
    $this->builder->addValue('strings-and-ints', ['uno', 1]);
})->throws(DumpException::class, 'Data types cannot be mixed in an array. Key: "strings-and-ints".');

test('addTable fails when duplicate tables', function (): void {
    $this->builder->addTable('a')
        ->addTable('a');
})->throws(DumpException::class, 'The table key "a" has already been defined previously.');

test('addTable fails when duplicate key table', function (): void {
    $this->builder->addTable('fruit')
        ->addValue('type', 'apple')
        ->addTable('fruit.type');
})->throws(DumpException::class, 'The table key "fruit.type" has already been defined previously.');

test('addValue fails when duplicate keys', function (): void {
    $this->builder->addValue('dupe', false)
        ->addValue('dupe', true);
})->throws(DumpException::class, 'The key "dupe" has already been defined previously.');

test('empty implicit key group fails', function (): void {
    $this->builder->addTable('naughty..naughty');
})->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null. Table: "naughty..naughty".');

test('addValue fails with null value', function (): void {
    $this->builder->addValue('theNull', null);
})->throws(\TypeError::class);

test('addValue fails with unsupported value type', function (): void {
    $this->builder->addValue('theNewClass', new class {});
})->throws(\TypeError::class);

test('addArrayOfTable fails when there is a table array implicit', function (): void {
    $this->builder->addArrayOfTable('albums.songs')
            ->addValue('name', 'Glory Days')
        ->addArrayOfTable('albums')
            ->addValue('name', 'Born in the USA');
})->throws(DumpException::class, 'The key "albums" has been defined as a implicit table from a previous array of tables.');

test('addTable fails with no unquoted keys', function (): void {
    $this->builder->addTable('valid key');
})->throws(DumpException::class, 'Only unquoted keys are allowed in this implementation. Key: "valid key".');
