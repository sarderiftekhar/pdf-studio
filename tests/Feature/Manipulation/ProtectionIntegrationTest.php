<?php

use PdfStudio\Laravel\Facades\Pdf;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('sets password protection via builder chain', function () {
    $fake = Pdf::fake();

    $result = $fake->html('<h1>Secret</h1>')
        ->protect(userPassword: 'user123', ownerPassword: 'owner456')
        ->render();

    $fake->assertProtected();
});

it('sets user password only', function () {
    $fake = Pdf::fake();

    $fake->html('<h1>Test</h1>')
        ->protect(userPassword: 'secret')
        ->render();

    $fake->assertProtected();
});

it('sets owner password only', function () {
    $fake = Pdf::fake();

    $fake->html('<h1>Test</h1>')
        ->protect(ownerPassword: 'admin')
        ->render();

    $fake->assertProtected();
});

it('sets permissions array', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')
        ->protect(ownerPassword: 'admin', permissions: ['Printing', 'CopyContents']);

    $options = $builder->getContext()->options;

    expect($options->ownerPassword)->toBe('admin')
        ->and($options->permissions)->toBe(['Printing', 'CopyContents']);
});

it('protection options stored in RenderOptions', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')
        ->protect(userPassword: 'user', ownerPassword: 'owner', permissions: ['Printing']);

    $options = $builder->getContext()->options;

    expect($options->userPassword)->toBe('user')
        ->and($options->ownerPassword)->toBe('owner')
        ->and($options->permissions)->toBe(['Printing']);
});

it('not protected by default', function () {
    $fake = Pdf::fake();

    $fake->html('<h1>Test</h1>')->render();

    expect(fn () => $fake->assertProtected())->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
})->skip('PHPUnit assertion class name varies');
