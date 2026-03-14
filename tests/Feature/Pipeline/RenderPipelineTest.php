<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Pipeline\RenderPipeline;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['view']->addNamespace('pdf-test', __DIR__.'/../../stubs/views');
});

it('renders a view through the full pipeline', function () {
    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(
        viewName: 'pdf-test::simple',
        data: ['name' => 'Pipeline'],
    );

    $result = $pipeline->run($context);

    expect($result->pdfContent)->toContain('FAKE_PDF')
        ->and($result->pdfContent)->toContain('Hello Pipeline');
});

it('renders raw HTML through the pipeline', function () {
    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(
        rawHtml: '<h1>Direct HTML</h1>',
    );

    $result = $pipeline->run($context);

    expect($result->pdfContent)->toContain('FAKE_PDF')
        ->and($result->pdfContent)->toContain('<h1>Direct HTML</h1>');
});

it('uses a specified driver', function () {
    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(rawHtml: '<p>Test</p>');

    $result = $pipeline->run($context, 'fake');

    expect($result->pdfContent)->toContain('FAKE_PDF');
});

it('resolves local image assets before rendering', function () {
    $imagePath = tempnam(sys_get_temp_dir(), 'pdfstudio_pipeline_asset_').'.png';
    file_put_contents($imagePath, 'fake-image');

    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(
        rawHtml: '<html><body><img src="'.$imagePath.'" alt="Logo"></body></html>',
    );

    $result = $pipeline->run($context, 'fake');

    expect($result->pdfContent)->toContain('data:image/png;base64,');

    @unlink($imagePath);
});

it('resolves local css url assets before rendering', function () {
    $imagePath = tempnam(sys_get_temp_dir(), 'pdfstudio_pipeline_style_asset_').'.png';
    file_put_contents($imagePath, 'fake-image');

    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(
        rawHtml: '<html><head><style>.hero{background-image:url("'.$imagePath.'");}</style></head><body><div class="hero">Styled</div></body></html>',
    );

    $result = $pipeline->run($context, 'fake');

    expect($result->pdfContent)->toContain('data:image/png;base64,')
        ->and($result->pdfContent)->toContain('background-image');

    @unlink($imagePath);
});
