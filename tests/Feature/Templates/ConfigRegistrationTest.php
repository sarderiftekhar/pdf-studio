<?php

use PdfStudio\Laravel\Templates\TemplateRegistry;

it('registers templates from config on boot', function () {
    $this->app['config']->set('pdf-studio.templates', [
        'invoice' => [
            'view' => 'pdf-studio::invoice',
            'description' => 'Standard invoice',
            'default_options' => ['format' => 'A4'],
        ],
        'report' => [
            'view' => 'pdf-studio::report',
            'description' => 'Monthly report',
            'default_options' => ['landscape' => true],
        ],
    ]);

    // Re-boot to pick up config
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $registry = $this->app->make(TemplateRegistry::class);

    expect($registry->has('invoice'))->toBeTrue()
        ->and($registry->has('report'))->toBeTrue()
        ->and($registry->get('invoice')->view)->toBe('pdf-studio::invoice')
        ->and($registry->get('invoice')->defaultOptions)->toBe(['format' => 'A4'])
        ->and($registry->get('report')->defaultOptions)->toBe(['landscape' => true]);
});

it('does not fail when no templates configured', function () {
    $this->app['config']->set('pdf-studio.templates', []);

    $registry = $this->app->make(TemplateRegistry::class);

    expect($registry->all())->toBeEmpty();
});
