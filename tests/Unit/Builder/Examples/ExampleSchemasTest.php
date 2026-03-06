<?php

use PdfStudio\Laravel\Builder\Examples\InvoiceSchema;
use PdfStudio\Laravel\Builder\Examples\ReportSchema;
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\SchemaValidator;

it('creates a valid invoice schema', function () {
    $schema = InvoiceSchema::create();

    expect($schema)->toBeInstanceOf(DocumentSchema::class)
        ->and($schema->version)->toBe('1.0')
        ->and($schema->blocks)->not->toBeEmpty();

    (new SchemaValidator)->validate($schema);
});

it('invoice schema round-trips through JSON', function () {
    $schema = InvoiceSchema::create();
    $json = $schema->toJson();
    $restored = DocumentSchema::fromJson($json);

    expect($restored->toArray())->toBe($schema->toArray());
});

it('creates a valid report schema', function () {
    $schema = ReportSchema::create();

    expect($schema)->toBeInstanceOf(DocumentSchema::class)
        ->and($schema->version)->toBe('1.0')
        ->and($schema->blocks)->not->toBeEmpty();

    (new SchemaValidator)->validate($schema);
});

it('report schema round-trips through JSON', function () {
    $schema = ReportSchema::create();
    $json = $schema->toJson();
    $restored = DocumentSchema::fromJson($json);

    expect($restored->toArray())->toBe($schema->toArray());
});
