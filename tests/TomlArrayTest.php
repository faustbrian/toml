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

use Cline\Toml\TomlArray;

beforeEach(function (): void {
    $this->tomlArray = new TomlArray();
});

test('getArray returns the key value added', function (): void {
    $this->tomlArray->addKeyValue('company', 'acme');

    expect($this->tomlArray->getArray())->toBe([
        'company' => 'acme',
    ]);
});

test('getArray returns the table with the key value', function (): void {
    $this->tomlArray->addTableKey('companyData');
    $this->tomlArray->addKeyValue('company', 'acme');

    expect($this->tomlArray->getArray())->toBe([
        'companyData' => [
            'company' => 'acme',
        ],
    ]);
});

test('getArray returns an array of tables', function (): void {
    $this->tomlArray->addArrayTableKey('companyData');
    $this->tomlArray->addKeyValue('company', 'acme1');
    $this->tomlArray->addArrayTableKey('companyData');
    $this->tomlArray->addKeyValue('company', 'acme2');

    expect($this->tomlArray->getArray())->toBe([
        'companyData' => [
            ['company' => 'acme1'],
            ['company' => 'acme2'],
        ],
    ]);
});
