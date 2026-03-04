<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Toml\Exception\DumpException;

describe('TomlBuilder Invalid', function (): void {
    test('addValue must fail when empty key', function (): void {
        createTomlBuilder()->addValue('', 'value');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

    test('addTable must fail when empty key', function (): void {
        createTomlBuilder()->addTable('');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

    test('addArrayOfTable must fail when empty key', function (): void {
        createTomlBuilder()->addArrayOfTable('');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

    test('addValue must fail when key with just white spaces', function (): void {
        createTomlBuilder()->addValue(' ', 'value');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

    test('addTable must fail when key with just white spaces', function (): void {
        createTomlBuilder()->addTable(' ');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

    test('addArrayOfTable must fail when key with just white spaces', function (): void {
        createTomlBuilder()->addArrayOfTable(' ');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null.');

    test('addValue must fail when mixed types', function (): void {
        createTomlBuilder()->addValue('strings-and-ints', ['uno', 1]);
    })->throws(DumpException::class, 'Data types cannot be mixed in an array. Key: "strings-and-ints".');

    test('addTable must fail when duplicate tables', function (): void {
        createTomlBuilder()
            ->addTable('a')
            ->addTable('a');
    })->throws(DumpException::class, 'The table key "a" has already been defined previously.');

    test('addTable must fail when duplicate key table', function (): void {
        createTomlBuilder()
            ->addTable('fruit')
            ->addValue('type', 'apple')
            ->addTable('fruit.type');
    })->throws(DumpException::class, 'The table key "fruit.type" has already been defined previously.');

    test('addValue must fail when duplicate keys', function (): void {
        createTomlBuilder()
            ->addValue('dupe', false)
            ->addValue('dupe', true);
    })->throws(DumpException::class, 'The key "dupe" has already been defined previously.');

    test('empty implicit key group', function (): void {
        createTomlBuilder()->addTable('naughty..naughty');
    })->throws(DumpException::class, 'A key, table name or array of table name cannot be empty or null. Table: "naughty..naughty".');

    test('addValue must fail with null value', function (): void {
        createTomlBuilder()->addValue('theNull', null);
    })->throws(DumpException::class, 'Data type not supporter at the key: "theNull".');

    test('addValue must fail with unsupported value type', function (): void {
        createTomlBuilder()->addValue('theNewClass', new class() {});
    })->throws(DumpException::class, 'Data type not supporter at the key: "theNewClass".');

    test('addArrayOfTable must fail when there is a table array implicit', function (): void {
        createTomlBuilder()
            ->addArrayOfTable('albums.songs')
            ->addValue('name', 'Glory Days')
            ->addArrayOfTable('albums')
            ->addValue('name', 'Born in the USA');
    })->throws(DumpException::class, 'The key "albums" has been defined as a implicit table from a previous array of tables.');

    test('addTable must fail with no unquoted keys', function (): void {
        createTomlBuilder()->addTable('valid key');
    })->throws(DumpException::class, 'Only unquoted keys are allowed in this implementation. Key: "valid key".');
});
