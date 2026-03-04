<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use Cline\Toml\Exception\InvalidArrayTableKeyException;
use Cline\Toml\Exception\InvalidKeyException;
use Cline\Toml\Exception\InvalidTableKeyException;

use function array_pop;
use function count;
use function explode;
use function implode;
use function in_array;
use function mb_trim;
use function sprintf;

/**
 * Manages the registry of keys, tables, and array of tables during TOML parsing.
 *
 * This internal class tracks all defined keys, tables, and array tables to enforce TOML's
 * uniqueness constraints and prevent redefinition errors. It maintains the parsing context
 * (current table/array table) and validates that new keys don't conflict with existing ones.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class KeyStore
{
    /**
     * All registered keys with their fully qualified paths.
     *
     * @var array<int, string>
     */
    private array $keys = [];

    /**
     * All explicitly defined table names.
     *
     * @var array<int, string>
     */
    private array $tables = [];

    /**
     * Array of tables with their occurrence counts for generating unique keys.
     *
     * @var array<string, int>
     */
    private array $arrayOfTables = [];

    /**
     * Implicitly created tables from nested array table definitions.
     *
     * @var array<int, string>
     */
    private array $implicitArrayOfTables = [];

    /**
     * The currently active table context for key resolution.
     */
    private string $currentTable = '';

    /**
     * The currently active array of tables context.
     */
    private string $currentArrayOfTable = '';

    /**
     * Registers a key in the current context (table or array table).
     *
     * @param string $name The key name to register
     *
     * @throws InvalidKeyException If the key already exists in the current context
     */
    public function addKey(string $name): void
    {
        if (!$this->isValidKey($name)) {
            throw InvalidKeyException::forKey($name);
        }

        $this->keys[] = $this->composeKeyWithCurrentPrefix($name);
    }

    /**
     * Checks whether a key can be registered in the current context.
     *
     * @param string $name The key name to validate
     *
     * @return bool True if the key doesn't conflict with existing keys
     */
    public function isValidKey(string $name): bool
    {
        $composedKey = $this->composeKeyWithCurrentPrefix($name);

        return !in_array($composedKey, $this->keys, true);
    }

    /**
     * Registers a standard TOML table [table.name] and updates parsing context.
     *
     * @param string $name The fully qualified table name
     *
     * @throws InvalidTableKeyException If the table key conflicts with existing definitions
     */
    public function addTableKey(string $name): void
    {
        if (!$this->isValidTableKey($name)) {
            throw InvalidTableKeyException::forKey($name);
        }

        $this->currentTable = '';
        $this->currentArrayOfTable = $this->getArrayOfTableKeyFromTableKey($name);
        $this->addKey($name);
        $this->currentTable = $name;
        $this->tables[] = $name;
    }

    /**
     * Validates whether a table name can be registered without conflicts.
     *
     * @param string $name The table name to validate
     *
     * @return bool True if the table can be safely registered
     */
    public function isValidTableKey(string $name): bool
    {
        $currentTable = $this->currentTable;
        $currentArrayOfTable = $this->currentArrayOfTable;

        $this->currentTable = '';
        $this->currentArrayOfTable = $this->getArrayOfTableKeyFromTableKey($name);

        if ($this->currentArrayOfTable === $name) {
            $this->currentTable = $currentTable;
            $this->currentArrayOfTable = $currentArrayOfTable;

            return false;
        }

        $isValid = $this->isValidKey($name);
        $this->currentTable = $currentTable;
        $this->currentArrayOfTable = $currentArrayOfTable;

        return $isValid;
    }

    /**
     * Validates whether an inline table definition is allowed.
     *
     * @param string $name The inline table name to validate
     *
     * @return bool True if the inline table can be registered
     */
    public function isValidInlineTable(string $name): bool
    {
        return $this->isValidTableKey($name);
    }

    /**
     * Registers an inline table {key = value} in the store.
     *
     * @param string $name The inline table name
     */
    public function addInlineTableKey(string $name): void
    {
        $this->addTableKey($name);
    }

    /**
     * Registers an array of tables [[array.table]] and updates parsing context.
     *
     * @param string $name The fully qualified array table name
     *
     * @throws InvalidArrayTableKeyException If the array table conflicts with existing keys
     */
    public function addArrayTableKey(string $name): void
    {
        if (!$this->isValidArrayTableKey($name)) {
            throw InvalidArrayTableKeyException::forKey($name);
        }

        $this->currentTable = '';
        $this->currentArrayOfTable = '';

        if (!isset($this->arrayOfTables[$name])) {
            $this->addKey($name);
            $this->arrayOfTables[$name] = 0;
        } else {
            ++$this->arrayOfTables[$name];
        }

        $this->currentArrayOfTable = $name;
        $this->processImplicitArrayTableNameIfNeeded($name);
    }

    /**
     * Validates whether an array table can be registered.
     *
     * An array table is valid if it's either completely new or already registered
     * as an array table (allowing multiple instances).
     *
     * @param string $name The array table name to validate
     *
     * @return bool True if the array table can be registered
     */
    public function isValidArrayTableKey(string $name): bool
    {
        $isInArrayOfTables = isset($this->arrayOfTables[$name]);
        $isInKeys = in_array($name, $this->keys, true);

        return (!$isInArrayOfTables && !$isInKeys) || ($isInArrayOfTables && $isInKeys);
    }

    /**
     * Checks whether a name has been explicitly defined as a table.
     *
     * @param string $name The table name to check
     *
     * @return bool True if the name is registered as a standard table
     */
    public function isRegisteredAsTableKey(string $name): bool
    {
        return in_array($name, $this->tables, true);
    }

    /**
     * Checks whether a name has been defined as an array of tables.
     *
     * @param string $name The array table name to check
     *
     * @return bool True if the name is registered as an array table
     */
    public function isRegisteredAsArrayTableKey(string $name): bool
    {
        return isset($this->arrayOfTables[$name]);
    }

    /**
     * Checks whether a table was implicitly created by a nested array table definition.
     *
     * When [[a.b.c]] is defined, tables 'a' and 'a.b' are implicitly created.
     * This method identifies such implicitly created tables.
     *
     * @param string $name The table name to check
     *
     * @return bool True if the table was implicitly created from an array table
     */
    public function isTableImplicitFromArryTable(string $name): bool
    {
        $isInImplicitArrayOfTables = in_array($name, $this->implicitArrayOfTables, true);
        $isInArrayOfTables = isset($this->arrayOfTables[$name]);

        return $isInImplicitArrayOfTables && !$isInArrayOfTables;
    }

    /**
     * Composes a fully qualified key path including current context prefixes.
     *
     * Combines the current array table (with index), current table, and the key name
     * to create a unique identifier for the key within the TOML structure.
     *
     * @param string $name The base key name
     *
     * @return string The fully qualified key path
     */
    private function composeKeyWithCurrentPrefix(string $name): string
    {
        $currentArrayOfTableIndex = '';

        if ($this->currentArrayOfTable !== '') {
            $currentArrayOfTableIndex = (string) $this->arrayOfTables[$this->currentArrayOfTable];
        }

        return mb_trim(sprintf('%s%s.%s.%s', $this->currentArrayOfTable, $currentArrayOfTableIndex, $this->currentTable, $name), '.');
    }

    /**
     * Determines the parent array table for a given table name.
     *
     * Traverses the table name hierarchy to find the nearest parent that is
     * registered as an array of tables. This is used to correctly scope nested
     * tables within array tables.
     *
     * @param string $name The table name to analyze
     *
     * @return string The parent array table name, or empty string if none exists
     */
    private function getArrayOfTableKeyFromTableKey(string $name): string
    {
        if (isset($this->arrayOfTables[$name])) {
            return $name;
        }

        $keyParts = explode('.', $name);

        if (count($keyParts) === 1) {
            return '';
        }

        array_pop($keyParts);

        while ($keyParts !== []) {
            $candidateKey = implode('.', $keyParts);

            if (isset($this->arrayOfTables[$candidateKey])) {
                return $candidateKey;
            }

            array_pop($keyParts);
        }

        return '';
    }

    /**
     * Records all implicitly created parent tables for a nested array table.
     *
     * When an array table like [[a.b.c]] is defined, parent tables 'a' and 'a.b'
     * are implicitly created. This method registers these intermediate tables
     * to prevent conflicts and enable proper validation.
     *
     * @param string $name The fully qualified array table name
     */
    private function processImplicitArrayTableNameIfNeeded(string $name): void
    {
        $nameParts = explode('.', $name);

        if (count($nameParts) < 2) {
            return;
        }

        array_pop($nameParts);

        while ($nameParts !== []) {
            $this->implicitArrayOfTables[] = implode('.', $nameParts);
            array_pop($nameParts);
        }
    }
}
