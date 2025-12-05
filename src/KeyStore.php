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

namespace Cline\Toml;

use Cline\Toml\Exception\InvalidKeyException;
use Cline\Toml\Exception\InvalidTableKeyException;
use Cline\Toml\Exception\InvalidArrayTableKeyException;

/**
 * Internal class for managing keys (key-values, tables and array of tables)
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class KeyStore
{
    /** @var list<string> */
    private array $keys = [];

    /** @var list<string> */
    private array $tables = [];

    /** @var array<string, int> */
    private array $arrayOfTables = [];

    /** @var list<string> */
    private array $implicitArrayOfTables = [];

    private string $currentTable = '';

    private string $currentArrayOfTable = '';

    public function addKey(string $name): void
    {
        if (! $this->isValidKey($name)) {
            throw InvalidKeyException::forName($name);
        }

        $this->keys[] = $this->composeKeyWithCurrentPrefix($name);
    }

    public function isValidKey(string $name): bool
    {
        $composedKey = $this->composeKeyWithCurrentPrefix($name);

        if (in_array($composedKey, $this->keys, true) === true) {
            return false;
        }

        return true;
    }

    public function addTableKey(string $name): void
    {
        if (! $this->isValidTableKey($name)) {
            throw InvalidTableKeyException::forName($name);
        }

        $this->currentTable = '';
        $this->currentArrayOfTable = $this->getArrayOfTableKeyFromTableKey($name);
        $this->addkey($name);
        $this->currentTable = $name;
        $this->tables[] = $name;
    }

    public function isValidTableKey(string $name): bool
    {
        $currentTable = $this->currentTable;
        $currentArrayOfTable = $this->currentArrayOfTable;

        $this->currentTable = '';
        $this->currentArrayOfTable = $this->getArrayOfTableKeyFromTableKey($name);

        if ($this->currentArrayOfTable === $name) {
            return false;
        }

        $isValid = $this->isValidKey($name);
        $this->currentTable = $currentTable;
        $this->currentArrayOfTable = $currentArrayOfTable;

        return $isValid;
    }

    public function isValidInlineTable(string $name): bool
    {
        return $this->isValidTableKey($name);
    }

    public function addInlineTableKey(string $name): void
    {
        $this->addTableKey($name);
    }

    public function addArrayTableKey(string $name): void
    {
        if (! $this->isValidArrayTableKey($name)) {
            throw InvalidArrayTableKeyException::forName($name);
        }

        $this->currentTable = '';
        $this->currentArrayOfTable = '';

        if (isset($this->arrayOfTables[$name]) === false) {
            $this->addkey($name);
            $this->arrayOfTables[$name] = 0;
        } else {
            $this->arrayOfTables[$name]++;
        }

        $this->currentArrayOfTable = $name;
        $this->processImplicitArrayTableNameIfNeeded($name);
    }

    public function isValidArrayTableKey(string $name): bool
    {
        $isInArrayOfTables = isset($this->arrayOfTables[$name]);
        $isInKeys = in_array($name, $this->keys, true);

        if ((! $isInArrayOfTables && ! $isInKeys) || ($isInArrayOfTables && $isInKeys)) {
            return true;
        }

        return false;
    }

    public function isRegisteredAsTableKey(string $name): bool
    {
        return in_array($name, $this->tables, true);
    }

    public function isRegisteredAsArrayTableKey(string $name): bool
    {
        return isset($this->arrayOfTables[$name]);
    }

    public function isTableImplicitFromArryTable(string $name): bool
    {
        $isInImplicitArrayOfTables = in_array($name, $this->implicitArrayOfTables, true);
        $isInArrayOfTables = isset($this->arrayOfTables[$name]);

        if ($isInImplicitArrayOfTables && ! $isInArrayOfTables) {
            return true;
        }

        return false;
    }

    private function composeKeyWithCurrentPrefix(string $name): string
    {
        $currentArrayOfTableIndex = '';

        if ($this->currentArrayOfTable !== '') {
            $currentArrayOfTableIndex = (string) $this->arrayOfTables[$this->currentArrayOfTable];
        }

        return \trim("{$this->currentArrayOfTable}{$currentArrayOfTableIndex}.{$this->currentTable}.{$name}", '.');
    }

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

        while (count($keyParts) > 0) {
            $candidateKey = implode('.', $keyParts);

            if (isset($this->arrayOfTables[$candidateKey])) {
                return $candidateKey;
            }

            array_pop($keyParts);
        }

        return '';
    }

    private function processImplicitArrayTableNameIfNeeded(string $name): void
    {
        $nameParts = explode('.', $name);

        if (count($nameParts) < 2) {
            return;
        }

        array_pop($nameParts);

        while (count($nameParts) !== 0) {
            $this->implicitArrayOfTables[] = implode('.', $nameParts);
            array_pop($nameParts);
        }
    }
}
