<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

describe('TomlArray', function (): void {
    test('getArray must return the key value added', function (): void {
        $tomlArray = createTomlArray();
        $tomlArray->addKeyValue('company', 'acme');

        expect($tomlArray->getArray())->toBe([
            'company' => 'acme',
        ]);
    });

    test('getArray must return the table with the key value', function (): void {
        $tomlArray = createTomlArray();
        $tomlArray->addTableKey('companyData');
        $tomlArray->addKeyValue('company', 'acme');

        expect($tomlArray->getArray())->toBe([
            'companyData' => [
                'company' => 'acme',
            ],
        ]);
    });

    test('getArray must return an array of tables', function (): void {
        $tomlArray = createTomlArray();
        $tomlArray->addArrayTableKey('companyData');
        $tomlArray->addKeyValue('company', 'acme1');
        $tomlArray->addArrayTableKey('companyData');
        $tomlArray->addKeyValue('company', 'acme2');

        expect($tomlArray->getArray())->toBe([
            'companyData' => [
                [
                    'company' => 'acme1',
                ],
                [
                    'company' => 'acme2',
                ],
            ],
        ]);
    });
});
