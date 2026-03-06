<?php

use PdfStudio\Laravel\Models\ApiKey;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('has correct table name', function () {
    $model = new ApiKey;
    expect($model->getTable())->toBe('pdf_studio_api_keys');
});

it('has correct fillable attributes', function () {
    $model = new ApiKey;
    expect($model->getFillable())->toBe([
        'workspace_id',
        'name',
        'key',
        'prefix',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ]);
});

it('checks if key is active', function () {
    $active = new ApiKey(['revoked_at' => null, 'expires_at' => null]);
    $revoked = new ApiKey(['revoked_at' => now()]);
    $expired = new ApiKey(['expires_at' => now()->subDay()]);

    expect($active->isActive())->toBeTrue()
        ->and($revoked->isActive())->toBeFalse()
        ->and($expired->isActive())->toBeFalse();
});

it('generates a new API key', function () {
    $result = ApiKey::generate();

    expect($result)->toHaveKeys(['key', 'prefix'])
        ->and($result['key'])->toHaveLength(64)
        ->and($result['prefix'])->toHaveLength(8);
});
