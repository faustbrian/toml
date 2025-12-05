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

/**
 * Internal class for managing a Toml array
 *
 * @author Victor Puertas <vpgugr@vpgugr.com>
 */
class TomlArray
{
    private const DOT_ESCAPED = '%*%';

    /** @var array<string, mixed> */
    private array $result = [];

    /** @var array<int|string, mixed> */
    private array $currentPointer = [];

    /** @var list<string> */
    private array $ArrayTableKeys = [];

    /** @var array<int|string, mixed> */
    private array $inlineTablePointers = [];

    public function __construct()
    {
        $this->resetCurrentPointer();
    }

    public function addKeyValue(string $name, mixed $value): void
    {
        $this->currentPointer[$name] = $value;
    }

    public function addTableKey(string $name): void
    {
        $this->resetCurrentPointer();
        $this->goToKey($name);
    }

    public function beginInlineTableKey(string $name): void
    {
        $this->inlineTablePointers[] = &$this->currentPointer;
        $this->goToKey($name);
    }

    public function endCurrentInlineTableKey(): void
    {
        $indexLastElement = $this->getKeyLastElementOfArray($this->inlineTablePointers);
        if ($indexLastElement !== null && isset($this->inlineTablePointers[$indexLastElement])) {
            /** @var array<int|string, mixed> $pointer */
            $pointer = &$this->inlineTablePointers[$indexLastElement];
            $this->currentPointer = &$pointer;
            unset($this->inlineTablePointers[$indexLastElement]);
        }
    }

    public function addArrayTableKey(string $name): void
    {
        $this->resetCurrentPointer();
        $this->goToKey($name);
        $this->currentPointer[] = [];
        $this->setCurrentPointerToLastElement();

        if (! $this->existsInArrayTableKey($name)) {
            $this->ArrayTableKeys[] = $name;
        }
    }

    public function escapeKey(string $name): string
    {
        return \str_replace('.', self::DOT_ESCAPED, $name);
    }

    /**
     * @return array<string, mixed>
     */
    public function getArray(): array
    {
        /** @var array<string, mixed> */
        return $this->result;
    }

    private function unescapeKey(string $name): string
    {
        return \str_replace(self::DOT_ESCAPED, '.', $name);
    }

    private function goToKey(string $name): void
    {
        $keyParts = explode('.', $name);
        $accumulatedKey = '';
        $countParts = count($keyParts);

        foreach ($keyParts as $index => $keyPart) {
            $keyPart = $this->unescapeKey($keyPart);
            $isLastKeyPart = $index === $countParts - 1;
            $accumulatedKey .= $accumulatedKey === '' ? $keyPart : '.' . $keyPart;

            if (\array_key_exists($keyPart, $this->currentPointer) === false) {
                $this->currentPointer[$keyPart] = [];
            }

            /** @var array<int|string, mixed> $next */
            $next = &$this->currentPointer[$keyPart];
            $this->currentPointer = &$next;

            if ($this->existsInArrayTableKey($accumulatedKey) && ! $isLastKeyPart) {
                $this->setCurrentPointerToLastElement();
            }
        }
    }

    private function setCurrentPointerToLastElement(): void
    {
        $indexLastElement = $this->getKeyLastElementOfArray($this->currentPointer);
        if ($indexLastElement !== null && isset($this->currentPointer[$indexLastElement])) {
            /** @var array<int|string, mixed> $next */
            $next = &$this->currentPointer[$indexLastElement];
            $this->currentPointer = &$next;
        }
    }

    private function resetCurrentPointer(): void
    {
        $this->currentPointer = &$this->result;
    }

    private function existsInArrayTableKey(string $name): bool
    {
        return \in_array($this->unescapeKey($name), $this->ArrayTableKeys, true);
    }

    /**
     * @param array<int|string, mixed> $arr
     */
    private function getKeyLastElementOfArray(array &$arr): int|string|null
    {
        end($arr);

        return key($arr);
    }
}
