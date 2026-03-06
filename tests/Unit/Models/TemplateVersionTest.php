<?php

use PdfStudio\Laravel\Models\TemplateVersion;

it('has correct table name', function () {
    $model = new TemplateVersion;
    expect($model->getTable())->toBe('pdf_studio_template_versions');
});

it('has correct fillable attributes', function () {
    $model = new TemplateVersion;
    expect($model->getFillable())->toBe([
        'template_name',
        'version_number',
        'view',
        'description',
        'default_options',
        'data_provider',
        'author',
        'change_notes',
    ]);
});

it('casts default_options to array', function () {
    $model = new TemplateVersion;
    $casts = $model->getCasts();
    expect($casts['default_options'])->toBe('array');
});

it('converts to TemplateDefinition DTO', function () {
    $model = new TemplateVersion([
        'template_name' => 'invoice',
        'version_number' => 1,
        'view' => 'pdf.invoice',
        'description' => 'Invoice template',
        'default_options' => ['format' => 'A4'],
        'data_provider' => null,
    ]);

    $dto = $model->toDefinition();

    expect($dto)->toBeInstanceOf(\PdfStudio\Laravel\DTOs\TemplateDefinition::class)
        ->and($dto->name)->toBe('invoice')
        ->and($dto->view)->toBe('pdf.invoice')
        ->and($dto->description)->toBe('Invoice template')
        ->and($dto->defaultOptions)->toBe(['format' => 'A4']);
});
