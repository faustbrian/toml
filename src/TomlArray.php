<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Toml;

use function array_key_exists;
use function array_key_last;
use function assert;
use function count;
use function explode;
use function in_array;
use function is_int;
use function str_replace;

/**
 * Builds nested array structures from parsed TOML data with hierarchical key management.
 *
 * TomlArray constructs the final PHP array representation by managing dotted key paths,
 * table hierarchies, and array-of-tables structures. It maintains internal pointers to
 * track the current insertion point as the parser processes different sections of the
 * TOML document, ensuring values are placed at correct nesting levels.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal Used internally by Parser; not intended for direct use
 */
final class TomlArray
{
    /**
     * Placeholder for escaped dots in key names to distinguish literal dots from path separators.
     */
    private const string DOT_ESCAPED = '%*%';

    /**
     * Root array structure containing all parsed TOML data.
     *
     * @var array<string, mixed>
     */
    private array $result = [];

    /**
     * Reference to current position in the nested array structure for inserting values.
     *
     * @var array<int|string, mixed>
     */
    private array $currentPointer;

    /**
     * Stack of pointers saved when entering inline tables, restored when exiting them.
     *
     * @var array<int, array<int|string, mixed>>
     */
    private array $inlineTablePointers = [];

    /**
     * Registry of array-of-tables keys to handle implicit table creation during traversal.
     *
     * @var array<int, string>
     */
    private array $ArrayTableKeys = [];

    /**
     * Create a new TOML array builder.
     *
     * Initializes the root array and sets the current pointer to the root position
     * for receiving top-level key-value pairs.
     */
    public function __construct()
    {
        $this->resetCurrentPointer();
    }

    /**
     * Add a key-value pair at the current pointer position.
     *
     * Inserts a value into the array structure at the current nesting level,
     * which may be the root, a table, or an inline table depending on the
     * parser's current context.
     *
     * @param string $name  Key name (may be escaped with DOT_ESCAPED placeholder)
     * @param mixed  $value Parsed value of any TOML type (scalar, array, DateTime)
     */
    public function addKeyValue(string $name, mixed $value): void
    {
        $this->currentPointer[$name] = $value;
    }

    /**
     * Navigate to a table section and set it as the current insertion point.
     *
     * Resets the pointer to root, then navigates through the dotted key path
     * to reach the table's position. Subsequent key-value pairs will be added
     * to this table until a different table is entered.
     *
     * @param string $name Fully qualified table name with escaped dots (e.g., "server.database")
     */
    public function addTableKey(string $name): void
    {
        $this->resetCurrentPointer();
        $this->goToKey($name);
    }

    /**
     * Enter an inline table context and save the current pointer for restoration.
     *
     * Pushes the current pointer onto a stack and navigates into the inline table.
     * The pointer will be restored when endCurrentInlineTableKey() is called,
     * allowing inline tables to be parsed without affecting the outer context.
     *
     * @param string $name Key name for the inline table
     */
    public function beginInlineTableKey(string $name): void
    {
        $this->inlineTablePointers[] = &$this->currentPointer;
        $this->goToKey($name);
    }

    /**
     * Exit the current inline table and restore the previous pointer context.
     *
     * Pops the saved pointer from the stack and restores it as the current position,
     * returning insertion context to the outer scope after finishing an inline table.
     */
    public function endCurrentInlineTableKey(): void
    {
        $indexLastElement = array_key_last($this->inlineTablePointers);
        assert(is_int($indexLastElement));

        /** @var array<int|string, mixed> $pointer */
        $pointer = &$this->inlineTablePointers[$indexLastElement];
        $this->currentPointer = &$pointer;
        unset($this->inlineTablePointers[$indexLastElement]);
    }

    /**
     * Create a new array-of-tables element and set it as the current insertion point.
     *
     * Navigates to the array-of-tables location, appends a new empty array element,
     * and positions the pointer inside it. Subsequent key-value pairs populate this
     * new element until another table or array-of-tables header is encountered.
     *
     * @param string $name Fully qualified array-of-tables name (e.g., "products")
     */
    public function addArrayTableKey(string $name): void
    {
        $this->resetCurrentPointer();
        $this->goToKey($name);
        $this->currentPointer[] = [];
        $this->setCurrentPointerToLastElement();

        if ($this->existsInArrayTableKey($name)) {
            return;
        }

        $this->ArrayTableKeys[] = $name;
    }

    /**
     * Escape literal dots in key names to distinguish them from path separators.
     *
     * Replaces dot characters with a placeholder to prevent them from being
     * interpreted as path delimiters in dotted keys. The placeholder is later
     * unescaped when constructing the final array structure.
     *
     * @param string $name Raw key name that may contain literal dots
     *
     * @return string Key name with dots replaced by DOT_ESCAPED placeholder
     */
    public function escapeKey(string $name): string
    {
        return str_replace('.', self::DOT_ESCAPED, $name);
    }

    /**
     * Retrieve the complete parsed TOML structure as a PHP array.
     *
     * Returns the root array containing all tables, arrays, and key-value pairs
     * constructed during parsing. This is the final output passed back to the Parser.
     *
     * @return array<string, mixed> Complete nested array representing the TOML document
     */
    public function getArray(): array
    {
        return $this->result;
    }

    private function unescapeKey(string $name): string
    {
        return str_replace(self::DOT_ESCAPED, '.', $name);
    }

    private function goToKey(string $name): void
    {
        $keyParts = explode('.', $name);
        $accumulatedKey = '';
        $countParts = count($keyParts);

        foreach ($keyParts as $index => $keyPart) {
            $keyPart = $this->unescapeKey($keyPart);
            $isLastKeyPart = $index === $countParts - 1;
            $accumulatedKey .= $accumulatedKey === '' ? $keyPart : '.'.$keyPart;

            if (!array_key_exists($keyPart, $this->currentPointer)) {
                $this->currentPointer[$keyPart] = [];
            }

            /** @var array<int|string, mixed> $nested */
            $nested = &$this->currentPointer[$keyPart];
            $this->currentPointer = &$nested;

            if (!$this->existsInArrayTableKey($accumulatedKey)) {
                continue;
            }

            if ($isLastKeyPart) {
                continue;
            }

            $this->setCurrentPointerToLastElement();
        }
    }

    private function setCurrentPointerToLastElement(): void
    {
        $indexLastElement = array_key_last($this->currentPointer);
        assert($indexLastElement !== null);

        /** @var array<int|string, mixed> $lastElement */
        $lastElement = &$this->currentPointer[$indexLastElement];
        $this->currentPointer = &$lastElement;
    }

    private function resetCurrentPointer(): void
    {
        $this->currentPointer = &$this->result;
    }

    private function existsInArrayTableKey(string $name): bool
    {
        return in_array($this->unescapeKey($name), $this->ArrayTableKeys, true);
    }
}
