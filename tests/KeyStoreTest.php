<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

describe('KeyStore', function (): void {
    test('isValidKey must return true when the key does not exist', function (): void {
        expect(createKeyStore()->isValidKey('a'))->toBeTrue();
    });

    test('isValidKey must return false when duplicate keys', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addKey('a');

        expect($keyStore->isValidKey('a'))->toBeFalse();
    });

    test('isValidTableKey must return true when the table key does not exist', function (): void {
        expect(createKeyStore()->isValidTableKey('a'))->toBeTrue();
    });

    test('isValidTableKey must return true when super table is not directly defined', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addTableKey('a.b');
        $keyStore->addKey('c');

        expect($keyStore->isValidTableKey('a'))->toBeTrue();
    });

    test('isValidTableKey must return false when duplicate table keys', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addTableKey('a');

        expect($keyStore->isValidTableKey('a'))->toBeFalse();
    });

    test('isValidTableKey must return false when there is a key with the same name', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addTableKey('a');
        $keyStore->addKey('b');

        expect($keyStore->isValidTableKey('a.b'))->toBeFalse();
    });

    test('isValidArrayTableKey must return false when there is a previous key with the same name', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addKey('a');

        expect($keyStore->isValidArrayTableKey('a'))->toBeFalse();
    });

    test('isValidArrayTableKey must return false when there is a previous table with the same name', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addTableKey('a');

        expect($keyStore->isValidArrayTableKey('a'))->toBeFalse();
    });

    test('isValidTableKey must return false when attempting to define a table key equal to previous defined array table', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addArrayTableKey('a');
        $keyStore->addArrayTableKey('a.b');

        expect($keyStore->isValidTableKey('a.b'))->toBeFalse();
    });

    test('isValidTableKey must return true with tables inside array of tables', function (): void {
        $keyStore = createKeyStore();
        $keyStore->addArrayTableKey('a');
        $keyStore->addTableKey('a.b');
        $keyStore->addArrayTableKey('a');

        expect($keyStore->isValidTableKey('a.b'))->toBeTrue();
    });
});
