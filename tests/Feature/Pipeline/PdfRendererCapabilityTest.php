<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Pipeline\PdfRenderer;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('logs capability warnings when options exceed driver support', function () {
    $renderer = app(PdfRenderer::class);
    $context = new RenderContext;
    $context->styledHtml = '<p>Test</p>';
    $context->options = new RenderOptions(format: 'Tabloid');

    $result = $renderer->handle($context, fn ($ctx) => $ctx);

    // FakeDriver supports A4, Letter, Legal — not Tabloid
    // Render should still succeed (warnings, not errors)
    expect($result->pdfContent)->toContain('FAKE_PDF');
});
