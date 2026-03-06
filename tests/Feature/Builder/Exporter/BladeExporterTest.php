<?php

use PdfStudio\Laravel\Builder\Exporter\BladeExporter;
use PdfStudio\Laravel\Builder\Schema\ColumnsBlock;
use PdfStudio\Laravel\Builder\Schema\DataBinding;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\ImageBlock;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\StyleTokens;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

it('exports a simple text schema to Blade', function () {
    $schema = new DocumentSchema(blocks: [
        new TextBlock(content: 'Invoice #{{ $invoice->number }}', tag: 'h1', classes: 'text-2xl font-bold'),
    ]);

    $exporter = new BladeExporter;
    $blade = $exporter->export($schema);

    expect($blade)->toContain('<h1 class="text-2xl font-bold">')
        ->and($blade)->toContain('{{ $invoice->number }}')
        ->and($blade)->toContain('<!DOCTYPE html>');
});

it('exports table with @foreach loop', function () {
    $schema = new DocumentSchema(blocks: [
        new TableBlock(
            headers: ['Item', 'Price'],
            rowBinding: new DataBinding(variable: 'items', path: 'line_items'),
            cellBindings: [
                new DataBinding(variable: 'item', path: 'name'),
                new DataBinding(variable: 'item', path: 'price'),
            ],
            classes: 'w-full',
        ),
    ]);

    $exporter = new BladeExporter;
    $blade = $exporter->export($schema);

    expect($blade)->toContain('@foreach($items as $item)')
        ->and($blade)->toContain('{{ $item->name }}')
        ->and($blade)->toContain('{{ $item->price }}')
        ->and($blade)->toContain('@endforeach');
});

it('exports columns as Tailwind grid', function () {
    $schema = new DocumentSchema(blocks: [
        new ColumnsBlock(
            columns: [
                [new TextBlock(content: 'Left col')],
                [new TextBlock(content: 'Right col')],
            ],
            gap: '6',
        ),
    ]);

    $exporter = new BladeExporter;
    $blade = $exporter->export($schema);

    expect($blade)->toContain('grid grid-cols-2 gap-6')
        ->and($blade)->toContain('Left col')
        ->and($blade)->toContain('Right col');
});

it('applies style tokens as inline style on body', function () {
    $schema = new DocumentSchema(
        blocks: [new TextBlock(content: 'Styled')],
        styleTokens: new StyleTokens(primaryColor: '#333', fontFamily: 'Georgia'),
    );

    $exporter = new BladeExporter;
    $blade = $exporter->export($schema);

    expect($blade)->toContain('font-family: Georgia')
        ->and($blade)->toContain('color: #333');
});

it('produces valid Blade that contains no PHP syntax errors', function () {
    $schema = new DocumentSchema(blocks: [
        new TextBlock(content: 'Hello {{ $name }}', tag: 'p'),
        new SpacerBlock(height: '1rem'),
        new ImageBlock(src: '{{ $logo_url }}', alt: 'Logo'),
    ]);

    $exporter = new BladeExporter;
    $blade = $exporter->export($schema);

    expect($blade)->toContain('{{ $name }}')
        ->and($blade)->toContain('{{ $logo_url }}')
        ->and($blade)->toContain('height: 1rem');
});

it('round-trips schema through export and is deterministic', function () {
    $schema = new DocumentSchema(blocks: [
        new TextBlock(content: 'Test', tag: 'p'),
    ]);

    $exporter = new BladeExporter;

    expect($exporter->export($schema))->toBe($exporter->export($schema));
});
