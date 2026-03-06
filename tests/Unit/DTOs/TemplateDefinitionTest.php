<?php

use PdfStudio\Laravel\DTOs\TemplateDefinition;

it('stores template definition properties', function () {
    $def = new TemplateDefinition(
        name: 'invoice',
        view: 'pdf-studio::invoice',
        description: 'Standard invoice template',
    );

    expect($def->name)->toBe('invoice')
        ->and($def->view)->toBe('pdf-studio::invoice')
        ->and($def->description)->toBe('Standard invoice template')
        ->and($def->defaultOptions)->toBe([])
        ->and($def->dataProvider)->toBeNull();
});

it('accepts default options and data provider', function () {
    $def = new TemplateDefinition(
        name: 'report',
        view: 'pdf-studio::report',
        defaultOptions: ['format' => 'Letter', 'landscape' => true],
        dataProvider: 'App\\DataProviders\\ReportDataProvider',
    );

    expect($def->defaultOptions)->toBe(['format' => 'Letter', 'landscape' => true])
        ->and($def->dataProvider)->toBe('App\\DataProviders\\ReportDataProvider');
});
