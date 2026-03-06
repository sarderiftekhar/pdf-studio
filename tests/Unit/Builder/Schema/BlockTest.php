<?php

use PdfStudio\Laravel\Builder\Schema\ColumnsBlock;
use PdfStudio\Laravel\Builder\Schema\DataBinding;
use PdfStudio\Laravel\Builder\Schema\ImageBlock;
use PdfStudio\Laravel\Builder\Schema\SpacerBlock;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

it('creates a text block', function () {
    $block = new TextBlock(content: 'Hello', tag: 'h1', classes: 'text-2xl');
    expect($block->type())->toBe('text')
        ->and($block->toArray())->toBe([
            'type' => 'text',
            'content' => 'Hello',
            'tag' => 'h1',
            'classes' => 'text-2xl',
        ]);
});

it('creates an image block', function () {
    $block = new ImageBlock(src: 'logo.png', alt: 'Logo', classes: 'w-32');
    expect($block->type())->toBe('image')
        ->and($block->toArray()['src'])->toBe('logo.png');
});

it('creates a table block', function () {
    $block = new TableBlock(
        headers: ['Name', 'Amount'],
        rowBinding: new DataBinding(variable: 'items', path: 'line_items'),
        cellBindings: [
            new DataBinding(variable: 'item', path: 'name'),
            new DataBinding(variable: 'item', path: 'amount'),
        ],
    );
    expect($block->type())->toBe('table')
        ->and($block->toArray()['headers'])->toBe(['Name', 'Amount']);
});

it('creates a columns block with nested blocks', function () {
    $block = new ColumnsBlock(
        columns: [
            [new TextBlock(content: 'Left')],
            [new TextBlock(content: 'Right')],
        ],
        gap: '4',
    );
    expect($block->type())->toBe('columns')
        ->and($block->toArray()['columns'])->toHaveCount(2);
});

it('creates a spacer block', function () {
    $block = new SpacerBlock(height: '2rem');
    expect($block->type())->toBe('spacer')
        ->and($block->toArray()['height'])->toBe('2rem');
});

it('serializes data binding', function () {
    $binding = new DataBinding(variable: 'invoice', path: 'total');
    expect($binding->toArray())->toBe([
        'variable' => 'invoice',
        'path' => 'total',
    ]);
});
