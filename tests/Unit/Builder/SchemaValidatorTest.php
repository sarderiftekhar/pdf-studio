<?php

use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\TextBlock;
use PdfStudio\Laravel\Builder\SchemaValidator;
use PdfStudio\Laravel\Exceptions\SchemaValidationException;

it('validates a valid schema', function () {
    $schema = new DocumentSchema(
        version: '1.0',
        blocks: [new TextBlock(content: 'Hello')],
    );

    $validator = new SchemaValidator;

    expect($validator->validate($schema))->toBeTrue();
});

it('rejects schema with no blocks', function () {
    $schema = new DocumentSchema(version: '1.0', blocks: []);

    $validator = new SchemaValidator;

    expect(fn () => $validator->validate($schema))
        ->toThrow(SchemaValidationException::class, 'at least one block');
});

it('rejects unknown version', function () {
    $schema = new DocumentSchema(version: '99.0', blocks: [new TextBlock(content: 'Hi')]);

    $validator = new SchemaValidator;

    expect(fn () => $validator->validate($schema))
        ->toThrow(SchemaValidationException::class, 'Unsupported schema version');
});

it('validates from raw JSON', function () {
    $json = json_encode([
        'version' => '1.0',
        'blocks' => [['type' => 'text', 'content' => 'Hello', 'tag' => 'p', 'classes' => '']],
        'style_tokens' => ['primary_color' => '#000', 'font_family' => 'sans-serif', 'font_size' => '16px', 'line_height' => '1.5'],
    ]);

    $validator = new SchemaValidator;

    expect($validator->validateJson($json))->toBeTrue();
});

it('rejects invalid JSON', function () {
    $validator = new SchemaValidator;

    expect(fn () => $validator->validateJson('not json'))
        ->toThrow(SchemaValidationException::class, 'Invalid JSON');
});
