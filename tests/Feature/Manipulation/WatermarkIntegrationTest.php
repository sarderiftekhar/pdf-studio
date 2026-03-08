<?php

use PdfStudio\Laravel\DTOs\WatermarkOptions;
use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Manipulation\WatermarkBuilder;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('sets text watermark via builder chain', function () {
    $fake = Pdf::fake();

    $result = $fake->html('<h1>Test</h1>')
        ->watermark('DRAFT')
        ->render();

    $fake->assertWatermarked();
});

it('sets text watermark with custom options', function () {
    $fake = Pdf::fake();

    $result = $fake->html('<h1>Test</h1>')
        ->watermark('CONFIDENTIAL', opacity: 0.5, fontSize: 72, color: '#FF0000', position: 'top-left')
        ->render();

    $fake->assertWatermarked();
    $fake->assertRendered();
});

it('sets image watermark via builder chain', function () {
    $fake = Pdf::fake();

    $result = $fake->html('<h1>Test</h1>')
        ->watermarkImage('/path/to/logo.png', opacity: 0.2, position: 'bottom-right')
        ->render();

    $fake->assertWatermarked();
});

it('watermarkPdf returns WatermarkBuilder instance', function () {
    $builder = Pdf::watermarkPdf('FAKE_PDF_BYTES');

    expect($builder)->toBeInstanceOf(WatermarkBuilder::class);
});

it('watermark options are stored in RenderOptions', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')
        ->watermark('DRAFT', opacity: 0.7, rotation: -30);

    $options = $builder->getContext()->options;

    expect($options->watermark)->toBeInstanceOf(WatermarkOptions::class)
        ->and($options->watermark->text)->toBe('DRAFT')
        ->and($options->watermark->opacity)->toBe(0.7)
        ->and($options->watermark->rotation)->toBe(-30);
});

it('watermarkImage options are stored in RenderOptions', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')
        ->watermarkImage('/path/to/logo.png', opacity: 0.4, position: 'top-right');

    $options = $builder->getContext()->options;

    expect($options->watermark)->toBeInstanceOf(WatermarkOptions::class)
        ->and($options->watermark->imagePath)->toBe('/path/to/logo.png')
        ->and($options->watermark->opacity)->toBe(0.4)
        ->and($options->watermark->position)->toBe('top-right');
});
