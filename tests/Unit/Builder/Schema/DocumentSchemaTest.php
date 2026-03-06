<?php

use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\StyleTokens;
use PdfStudio\Laravel\Builder\Schema\TextBlock;

it('creates a document schema with blocks', function () {
    $schema = new DocumentSchema(
        version: '1.0',
        blocks: [new TextBlock(content: 'Hello')],
    );

    expect($schema->version)->toBe('1.0')
        ->and($schema->blocks)->toHaveCount(1);
});

it('serializes to array', function () {
    $schema = new DocumentSchema(
        version: '1.0',
        blocks: [new TextBlock(content: 'Hello')],
        styleTokens: new StyleTokens(
            primaryColor: '#000000',
            fontFamily: 'Inter',
        ),
    );

    $array = $schema->toArray();

    expect($array)->toHaveKeys(['version', 'blocks', 'style_tokens'])
        ->and($array['version'])->toBe('1.0')
        ->and($array['blocks'])->toHaveCount(1)
        ->and($array['blocks'][0]['type'])->toBe('text');
});

it('deserializes from array', function () {
    $data = [
        'version' => '1.0',
        'blocks' => [
            ['type' => 'text', 'content' => 'Hello', 'tag' => 'p', 'classes' => ''],
        ],
        'style_tokens' => [
            'primary_color' => '#000000',
            'font_family' => 'Inter',
            'font_size' => '16px',
            'line_height' => '1.5',
        ],
    ];

    $schema = DocumentSchema::fromArray($data);

    expect($schema->version)->toBe('1.0')
        ->and($schema->blocks)->toHaveCount(1)
        ->and($schema->blocks[0])->toBeInstanceOf(TextBlock::class);
});

it('round-trips through serialization', function () {
    $schema = new DocumentSchema(
        version: '1.0',
        blocks: [
            new TextBlock(content: 'Hello World', tag: 'h1', classes: 'text-2xl font-bold'),
        ],
    );

    $restored = DocumentSchema::fromArray($schema->toArray());

    expect($restored->toArray())->toBe($schema->toArray());
});
