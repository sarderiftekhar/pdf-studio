<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\DTOs\RenderOptions;

it('initializes with default RenderOptions', function () {
    $context = new RenderContext;

    expect($context->options)->toBeInstanceOf(RenderOptions::class)
        ->and($context->viewName)->toBeNull()
        ->and($context->rawHtml)->toBeNull()
        ->and($context->data)->toBe([]);
});

it('accepts custom values', function () {
    $context = new RenderContext(
        viewName: 'invoices.show',
        data: ['invoice' => ['id' => 1]],
    );

    expect($context->viewName)->toBe('invoices.show')
        ->and($context->data)->toBe(['invoice' => ['id' => 1]]);
});

it('defaults cssFramework to null', function () {
    $context = new RenderContext;
    expect($context->cssFramework)->toBeNull();
});

it('accepts cssFramework parameter', function () {
    $context = new RenderContext(cssFramework: 'bootstrap');
    expect($context->cssFramework)->toBe('bootstrap');
});
