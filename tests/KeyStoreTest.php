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

use Cline\Toml\KeyStore;

beforeEach(function (): void {
    $this->keyStore = new KeyStore();
});

test('isValidKey returns true when the key does not exist', function (): void {
    expect($this->keyStore->isValidKey('a'))->toBeTrue();
});

test('isValidKey returns false when duplicate keys', function (): void {
    $this->keyStore->addKey('a');

    expect($this->keyStore->isValidKey('a'))->toBeFalse();
});

test('isValidTableKey returns true when the table key does not exist', function (): void {
    expect($this->keyStore->isValidTableKey('a'))->toBeTrue();
});

test('isValidTableKey returns true when super table is not directly defined', function (): void {
    $this->keyStore->addTableKey('a.b');
    $this->keyStore->addKey('c');

    expect($this->keyStore->isValidTableKey('a'))->toBeTrue();
});

test('isValidTableKey returns false when duplicate table keys', function (): void {
    $this->keyStore->addTableKey('a');

    expect($this->keyStore->isValidTableKey('a'))->toBeFalse();
});

test('isValidTableKey returns false when there is a key with the same name', function (): void {
    $this->keyStore->addTableKey('a');
    $this->keyStore->addKey('b');

    expect($this->keyStore->isValidTableKey('a.b'))->toBeFalse();
});

test('isValidArrayTableKey returns false when there is a previous key with the same name', function (): void {
    $this->keyStore->addKey('a');

    expect($this->keyStore->isValidArrayTableKey('a'))->toBeFalse();
});

test('isValidArrayTableKey returns false when there is a previous table with the same name', function (): void {
    $this->keyStore->addTableKey('a');

    expect($this->keyStore->isValidArrayTableKey('a'))->toBeFalse();
});

test('isValidTableKey returns false when attempting to define a table key equal to previously defined array table', function (): void {
    $this->keyStore->addArrayTableKey('a');
    $this->keyStore->addArrayTableKey('a.b');

    expect($this->keyStore->isValidTableKey('a.b'))->toBeFalse();
});

test('isValidTableKey returns true with tables inside array of tables', function (): void {
    $this->keyStore->addArrayTableKey('a');
    $this->keyStore->addTableKey('a.b');
    $this->keyStore->addArrayTableKey('a');

    expect($this->keyStore->isValidTableKey('a.b'))->toBeTrue();
});
