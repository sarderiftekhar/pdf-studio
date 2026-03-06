<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Pipeline\CssInjector;

it('injects CSS into HTML head', function () {
    $injector = new CssInjector;
    $context = new RenderContext;
    $context->compiledHtml = '<html><head></head><body><h1>Test</h1></body></html>';
    $context->compiledCss = 'h1 { color: red; }';

    $result = $injector->handle($context, fn ($ctx) => $ctx);

    expect($result->styledHtml)->toContain('<style>h1 { color: red; }</style>')
        ->and($result->styledHtml)->toContain('<h1>Test</h1>');
});

it('wraps HTML in document structure when no head tag exists', function () {
    $injector = new CssInjector;
    $context = new RenderContext;
    $context->compiledHtml = '<h1>No wrapper</h1>';
    $context->compiledCss = 'h1 { color: blue; }';

    $result = $injector->handle($context, fn ($ctx) => $ctx);

    expect($result->styledHtml)->toContain('<style>h1 { color: blue; }</style>')
        ->and($result->styledHtml)->toContain('<h1>No wrapper</h1>');
});

it('passes through HTML unchanged when no CSS exists', function () {
    $injector = new CssInjector;
    $context = new RenderContext;
    $context->compiledHtml = '<html><body><p>Hello</p></body></html>';

    $result = $injector->handle($context, fn ($ctx) => $ctx);

    expect($result->styledHtml)->toBe('<html><body><p>Hello</p></body></html>');
});

it('passes context to the next stage', function () {
    $injector = new CssInjector;
    $context = new RenderContext;
    $context->compiledHtml = '<p>Test</p>';
    $nextCalled = false;

    $injector->handle($context, function ($ctx) use (&$nextCalled) {
        $nextCalled = true;

        return $ctx;
    });

    expect($nextCalled)->toBeTrue();
});
