<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Pipeline\PdfRenderer;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('renders styled HTML to PDF bytes via driver', function () {
    $renderer = app(PdfRenderer::class);
    $context = new RenderContext;
    $context->styledHtml = '<html><body><h1>Invoice</h1></body></html>';

    $result = $renderer->handle($context, fn ($ctx) => $ctx);

    expect($result->pdfContent)->toBeString()
        ->and($result->pdfContent)->toContain('FAKE_PDF')
        ->and($result->pdfContent)->toContain('<h1>Invoice</h1>');
});

it('uses styledHtml, falls back to compiledHtml', function () {
    $renderer = app(PdfRenderer::class);
    $context = new RenderContext;
    $context->compiledHtml = '<p>Fallback</p>';

    $result = $renderer->handle($context, fn ($ctx) => $ctx);

    expect($result->pdfContent)->toContain('<p>Fallback</p>');
});

it('uses the specified driver', function () {
    $renderer = app(PdfRenderer::class);
    $renderer->setDriver('fake');
    $context = new RenderContext;
    $context->styledHtml = '<p>Test</p>';

    $result = $renderer->handle($context, fn ($ctx) => $ctx);

    expect($result->pdfContent)->toContain('FAKE_PDF');
});

it('passes context to the next stage', function () {
    $renderer = app(PdfRenderer::class);
    $context = new RenderContext;
    $context->styledHtml = '<p>Test</p>';
    $nextCalled = false;

    $renderer->handle($context, function ($ctx) use (&$nextCalled) {
        $nextCalled = true;

        return $ctx;
    });

    expect($nextCalled)->toBeTrue();
});
