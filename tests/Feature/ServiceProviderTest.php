<?php

use PdfStudio\Laravel\PdfBuilder;

it('registers PdfBuilder as a transient binding', function () {
    $builder1 = app(PdfBuilder::class);
    $builder2 = app(PdfBuilder::class);

    expect($builder1)->toBeInstanceOf(PdfBuilder::class)
        ->and($builder2)->toBeInstanceOf(PdfBuilder::class)
        ->and($builder1)->not->toBe($builder2);
});

it('merges default config', function () {
    expect(config('pdf-studio.default_driver'))->toBe('chromium')
        ->and(config('pdf-studio.drivers'))->toBeArray()
        ->and(config('pdf-studio.drivers'))->toHaveKeys(['chromium', 'wkhtmltopdf', 'dompdf']);
});
