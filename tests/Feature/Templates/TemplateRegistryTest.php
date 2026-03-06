<?php

use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Exceptions\TemplateNotFoundException;
use PdfStudio\Laravel\Templates\TemplateRegistry;

beforeEach(function () {
    $this->registry = new TemplateRegistry;
});

it('registers and resolves a template by name', function () {
    $def = new TemplateDefinition(name: 'invoice', view: 'pdf-studio::invoice');

    $this->registry->register($def);

    expect($this->registry->get('invoice'))->toBe($def);
});

it('throws TemplateNotFoundException for unknown template', function () {
    $this->registry->get('nonexistent');
})->throws(TemplateNotFoundException::class);

it('checks if a template is registered', function () {
    $def = new TemplateDefinition(name: 'invoice', view: 'pdf-studio::invoice');

    expect($this->registry->has('invoice'))->toBeFalse();

    $this->registry->register($def);

    expect($this->registry->has('invoice'))->toBeTrue();
});

it('lists all registered templates', function () {
    $this->registry->register(new TemplateDefinition(name: 'invoice', view: 'v1'));
    $this->registry->register(new TemplateDefinition(name: 'report', view: 'v2'));

    $all = $this->registry->all();

    expect($all)->toHaveCount(2)
        ->and(array_keys($all))->toBe(['invoice', 'report']);
});

it('allows overriding a registered template', function () {
    $original = new TemplateDefinition(name: 'invoice', view: 'package::invoice');
    $override = new TemplateDefinition(name: 'invoice', view: 'app.invoice');

    $this->registry->register($original);
    $this->registry->register($override);

    expect($this->registry->get('invoice')->view)->toBe('app.invoice');
});
