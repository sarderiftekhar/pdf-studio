<?php

use PdfStudio\Laravel\PdfBuilder;

it('has thumbnail method on PdfBuilder', function () {
    $builder = app(PdfBuilder::class);

    expect(method_exists($builder, 'thumbnail'))->toBeTrue();
});

it('thumbnail config is loaded', function () {
    expect(config('pdf-studio.thumbnail'))->toBeArray();
    expect(config('pdf-studio.thumbnail.strategy'))->toBe('auto');
    expect(config('pdf-studio.thumbnail.default_width'))->toBe(300);
    expect(config('pdf-studio.thumbnail.default_format'))->toBe('png');
    expect(config('pdf-studio.thumbnail.quality'))->toBe(85);
});
