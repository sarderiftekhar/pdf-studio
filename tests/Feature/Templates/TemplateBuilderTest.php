<?php

use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Exceptions\TemplateNotFoundException;
use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Templates\TemplateRegistry;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');

    // Register a test view
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');
});

it('resolves a registered template via PdfBuilder', function () {
    $registry = $this->app->make(TemplateRegistry::class);
    $registry->register(new TemplateDefinition(
        name: 'simple-test',
        view: 'simple',
    ));

    $result = Pdf::template('simple-test')
        ->data(['name' => 'World'])
        ->render();

    expect($result->content())->toContain('Hello World');
});

it('applies default options from template definition', function () {
    $registry = $this->app->make(TemplateRegistry::class);
    $registry->register(new TemplateDefinition(
        name: 'landscape-report',
        view: 'simple',
        defaultOptions: ['format' => 'Letter', 'landscape' => true],
    ));

    $builder = Pdf::template('landscape-report')->data(['name' => 'Test']);
    $context = $builder->getContext();

    expect($context->options->format)->toBe('Letter')
        ->and($context->options->landscape)->toBeTrue();
});

it('throws TemplateNotFoundException for unregistered template', function () {
    Pdf::template('nonexistent');
})->throws(TemplateNotFoundException::class);
