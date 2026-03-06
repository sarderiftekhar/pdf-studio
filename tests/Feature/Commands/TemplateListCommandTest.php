<?php

use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Templates\TemplateRegistry;

it('lists registered templates', function () {
    $registry = $this->app->make(TemplateRegistry::class);
    $registry->register(new TemplateDefinition(
        name: 'invoice',
        view: 'pdf-studio::invoice',
        description: 'Standard invoice',
    ));
    $registry->register(new TemplateDefinition(
        name: 'report',
        view: 'pdf-studio::report',
        description: 'Monthly report',
    ));

    $this->artisan('pdf-studio:templates')
        ->expectsOutputToContain('invoice')
        ->expectsOutputToContain('report')
        ->assertSuccessful();
});

it('shows message when no templates are registered', function () {
    $this->artisan('pdf-studio:templates')
        ->expectsOutputToContain('No templates registered')
        ->assertSuccessful();
});
