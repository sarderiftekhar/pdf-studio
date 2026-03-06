<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Models\TemplateVersion;
use PdfStudio\Laravel\Services\TemplateVersionService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TemplateVersionService;
});

it('creates a version from a TemplateDefinition', function () {
    $definition = new TemplateDefinition(
        name: 'invoice',
        view: 'pdf.invoice',
        description: 'Invoice template',
        defaultOptions: ['format' => 'A4'],
    );

    $version = $this->service->create($definition, author: 'admin', changeNotes: 'Initial version');

    expect($version)->toBeInstanceOf(TemplateVersion::class)
        ->and($version->template_name)->toBe('invoice')
        ->and($version->version_number)->toBe(1)
        ->and($version->author)->toBe('admin')
        ->and($version->change_notes)->toBe('Initial version');
});

it('auto-increments version number per template', function () {
    $definition = new TemplateDefinition(name: 'invoice', view: 'pdf.invoice');

    $v1 = $this->service->create($definition, author: 'admin');
    $v2 = $this->service->create($definition, author: 'admin');

    expect($v1->version_number)->toBe(1)
        ->and($v2->version_number)->toBe(2);
});

it('lists all versions for a template ordered by version desc', function () {
    $definition = new TemplateDefinition(name: 'invoice', view: 'pdf.invoice');
    $this->service->create($definition, author: 'admin');
    $this->service->create($definition, author: 'admin');
    $this->service->create($definition, author: 'editor');

    $versions = $this->service->list('invoice');

    expect($versions)->toHaveCount(3)
        ->and($versions->first()->version_number)->toBe(3)
        ->and($versions->last()->version_number)->toBe(1);
});

it('restores a specific version to the registry', function () {
    $def1 = new TemplateDefinition(name: 'invoice', view: 'pdf.invoice-v1');
    $def2 = new TemplateDefinition(name: 'invoice', view: 'pdf.invoice-v2');

    $this->service->create($def1, author: 'admin');
    $this->service->create($def2, author: 'admin');

    $restored = $this->service->restore('invoice', 1);

    expect($restored->view)->toBe('pdf.invoice-v1');
});

it('returns diff metadata between two versions', function () {
    $def1 = new TemplateDefinition(
        name: 'invoice',
        view: 'pdf.invoice-v1',
        description: 'Original',
        defaultOptions: ['format' => 'A4'],
    );
    $def2 = new TemplateDefinition(
        name: 'invoice',
        view: 'pdf.invoice-v2',
        description: 'Updated',
        defaultOptions: ['format' => 'A4', 'landscape' => true],
    );

    $this->service->create($def1, author: 'admin');
    $this->service->create($def2, author: 'admin');

    $diff = $this->service->diff('invoice', 1, 2);

    expect($diff)->toBeArray()
        ->and($diff)->toHaveKeys(['from_version', 'to_version', 'changes'])
        ->and($diff['from_version'])->toBe(1)
        ->and($diff['to_version'])->toBe(2)
        ->and($diff['changes'])->toHaveKey('view')
        ->and($diff['changes'])->toHaveKey('description')
        ->and($diff['changes'])->toHaveKey('default_options');
});

it('throws when restoring non-existent version', function () {
    $this->service->restore('invoice', 99);
})->throws(\PdfStudio\Laravel\Exceptions\TemplateNotFoundException::class);

it('throws when diffing non-existent versions', function () {
    $this->service->diff('invoice', 1, 2);
})->throws(\PdfStudio\Laravel\Exceptions\TemplateNotFoundException::class);
