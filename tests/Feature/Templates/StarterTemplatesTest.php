<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Templates\TemplateRegistry;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['config']->set('pdf-studio.starter_templates', true);
});

it('registers starter templates when enabled', function () {
    // Re-boot to pick up config
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $registry = $this->app->make(TemplateRegistry::class);

    expect($registry->has('pdf-studio::invoice'))->toBeTrue()
        ->and($registry->has('pdf-studio::report'))->toBeTrue()
        ->and($registry->has('pdf-studio::certificate'))->toBeTrue();
});

it('does not register starter templates when disabled', function () {
    $this->app['config']->set('pdf-studio.starter_templates', false);

    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $registry = $this->app->make(TemplateRegistry::class);

    expect($registry->has('pdf-studio::invoice'))->toBeFalse();
});

it('renders the invoice starter template', function () {
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $result = Pdf::template('pdf-studio::invoice')
        ->data([
            'invoice_number' => 'INV-001',
            'date' => '2026-01-15',
            'company' => 'Acme Corp',
            'items' => [
                ['description' => 'Widget', 'quantity' => 2, 'price' => 10.00],
            ],
            'total' => 20.00,
        ])
        ->render();

    expect($result->content())->toContain('INV-001')
        ->and($result->content())->toContain('Acme Corp');
});

it('renders the report starter template', function () {
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $result = Pdf::template('pdf-studio::report')
        ->data([
            'title' => 'Monthly Report',
            'date' => '2026-01-15',
            'sections' => [
                ['heading' => 'Summary', 'content' => 'This is the summary.'],
            ],
        ])
        ->render();

    expect($result->content())->toContain('Monthly Report');
});

it('renders the certificate starter template', function () {
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $result = Pdf::template('pdf-studio::certificate')
        ->data([
            'recipient' => 'Jane Doe',
            'achievement' => 'Course Completion',
            'date' => '2026-01-15',
            'issuer' => 'Learning Academy',
        ])
        ->render();

    expect($result->content())->toContain('Jane Doe')
        ->and($result->content())->toContain('Course Completion');
});
