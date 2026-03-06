<?php

use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Debug\DebugRecorder;
use PdfStudio\Laravel\DTOs\RenderContext;

beforeEach(function () {
    Storage::fake('local');
    $this->app['config']->set('pdf-studio.debug', true);
});

it('dumps nothing when debug is disabled', function () {
    $this->app['config']->set('pdf-studio.debug', false);

    $recorder = app(DebugRecorder::class);
    $context = new RenderContext(rawHtml: '<h1>Test</h1>');
    $context->compiledHtml = '<h1>Test</h1>';
    $context->compiledCss = '.test { color: red; }';
    $context->styledHtml = '<style>.test { color: red; }</style><h1>Test</h1>';
    $context->pdfContent = 'FAKE_PDF';

    $recorder->record($context, 'fake', 42.5);

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('dumps artifacts to storage when debug is enabled', function () {
    $recorder = app(DebugRecorder::class);
    $context = new RenderContext(rawHtml: '<h1>Test</h1>');
    $context->compiledHtml = '<h1>Compiled</h1>';
    $context->compiledCss = '.test { color: red; }';
    $context->styledHtml = '<style>.test</style><h1>Compiled</h1>';
    $context->pdfContent = 'FAKE_PDF';

    $recorder->record($context, 'fake', 42.5);

    $files = Storage::disk('local')->allFiles();

    expect($files)->not->toBeEmpty();

    // Should contain HTML, CSS, and metadata files
    $hasHtml = false;
    $hasCss = false;
    $hasMeta = false;

    foreach ($files as $file) {
        if (str_contains($file, 'compiled.html')) {
            $hasHtml = true;
        }
        if (str_contains($file, 'compiled.css')) {
            $hasCss = true;
        }
        if (str_contains($file, 'metadata.json')) {
            $hasMeta = true;
        }
    }

    expect($hasHtml)->toBeTrue()
        ->and($hasCss)->toBeTrue()
        ->and($hasMeta)->toBeTrue();
});

it('includes timing and driver in metadata', function () {
    $recorder = app(DebugRecorder::class);
    $context = new RenderContext(rawHtml: '<h1>Test</h1>');
    $context->compiledHtml = '<h1>Compiled</h1>';
    $context->pdfContent = 'FAKE_PDF';

    $recorder->record($context, 'dompdf', 55.3);

    $files = Storage::disk('local')->allFiles();
    $metaFile = collect($files)->first(fn ($f) => str_contains($f, 'metadata.json'));

    expect($metaFile)->not->toBeNull();

    $meta = json_decode(Storage::disk('local')->get($metaFile), true);

    expect($meta['driver'])->toBe('dompdf')
        ->and($meta['render_time_ms'])->toBe(55.3);
});
