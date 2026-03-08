<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Manipulation\AcroFormBuilder;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('acroform returns AcroFormBuilder instance', function () {
    $builder = Pdf::acroform('/path/to/form.pdf');

    expect($builder)->toBeInstanceOf(AcroFormBuilder::class);
});

it('AcroFormBuilder supports fluent fill and flatten', function () {
    $builder = Pdf::acroform('/path/to/form.pdf');

    $result = $builder->fill(['name' => 'John Doe'])
        ->fill(['email' => 'john@example.com'])
        ->flatten();

    expect($result)->toBeInstanceOf(AcroFormBuilder::class);
});

it('AcroFormBuilder accumulates field values', function () {
    $builder = Pdf::acroform('/path/to/form.pdf');

    $builder->fill(['field1' => 'value1'])->fill(['field2' => 'value2']);

    $reflection = new ReflectionProperty($builder, 'fieldValues');
    $values = $reflection->getValue($builder);

    expect($values)->toBe(['field1' => 'value1', 'field2' => 'value2']);
});

it('PdfBuilder acroform method creates builder with correct path', function () {
    $builder = Pdf::acroform('/some/form.pdf');

    $reflection = new ReflectionProperty($builder, 'pdfPath');
    $path = $reflection->getValue($builder);

    expect($path)->toBe('/some/form.pdf');
});
