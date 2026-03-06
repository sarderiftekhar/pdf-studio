<?php

use PdfStudio\Laravel\Builder\Compiler\SchemaToHtmlCompiler;
use PdfStudio\Laravel\Builder\Schema\ColumnsBlock;
use PdfStudio\Laravel\Builder\Schema\DataBinding;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\ImageBlock;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\StyleTokens;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

it('compiles text block to HTML', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(blocks: [
        new TextBlock(content: 'Hello World', tag: 'h1', classes: 'text-2xl'),
    ]);

    $html = $compiler->compile($schema);

    expect($html)->toContain('<h1 class="text-2xl">Hello World</h1>');
});

it('compiles image block to HTML', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(blocks: [
        new ImageBlock(src: 'logo.png', alt: 'Logo', classes: 'w-32'),
    ]);

    $html = $compiler->compile($schema);

    expect($html)->toContain('<img src="logo.png" alt="Logo" class="w-32"');
});

it('compiles spacer block to HTML', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(blocks: [
        new SpacerBlock(height: '2rem'),
    ]);

    $html = $compiler->compile($schema);

    expect($html)->toContain('height: 2rem');
});

it('compiles table block with data binding placeholders', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(blocks: [
        new TableBlock(
            headers: ['Name', 'Price'],
            rowBinding: new DataBinding(variable: 'items', path: 'line_items'),
            cellBindings: [
                new DataBinding(variable: 'item', path: 'name'),
                new DataBinding(variable: 'item', path: 'price'),
            ],
        ),
    ]);

    $html = $compiler->compile($schema);

    expect($html)->toContain('<table')
        ->and($html)->toContain('<th>Name</th>')
        ->and($html)->toContain('<th>Price</th>')
        ->and($html)->toContain('{{ $item->name }}')
        ->and($html)->toContain('{{ $item->price }}');
});

it('compiles columns block to HTML grid', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(blocks: [
        new ColumnsBlock(
            columns: [
                [new TextBlock(content: 'Left')],
                [new TextBlock(content: 'Right')],
            ],
            gap: '4',
        ),
    ]);

    $html = $compiler->compile($schema);

    expect($html)->toContain('grid')
        ->and($html)->toContain('Left')
        ->and($html)->toContain('Right');
});

it('applies style tokens to wrapper', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(
        blocks: [new TextBlock(content: 'Styled')],
        styleTokens: new StyleTokens(
            primaryColor: '#ff0000',
            fontFamily: 'Inter',
        ),
    );

    $html = $compiler->compile($schema);

    expect($html)->toContain('font-family: Inter')
        ->and($html)->toContain('color: #ff0000');
});

it('wraps output in full HTML document', function () {
    $compiler = new SchemaToHtmlCompiler;
    $schema = new DocumentSchema(blocks: [new TextBlock(content: 'Test')]);

    $html = $compiler->compile($schema);

    expect($html)->toContain('<!DOCTYPE html>')
        ->and($html)->toContain('<html')
        ->and($html)->toContain('</html>');
});
