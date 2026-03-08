<?php

use PdfStudio\Laravel\Thumbnail\ThumbnailGenerator;
use PdfStudio\Laravel\Thumbnail\ImagickStrategy;
use PdfStudio\Laravel\Thumbnail\ChromiumStrategy;

it('resolves imagick strategy when available', function () {
    $generator = new ThumbnailGenerator(strategy: 'imagick');

    expect($generator->getStrategy())->toBeInstanceOf(ImagickStrategy::class);
})->skip(!extension_loaded('imagick'), 'Imagick extension not available');

it('resolves chromium strategy', function () {
    $generator = new ThumbnailGenerator(strategy: 'chromium');

    expect($generator->getStrategy())->toBeInstanceOf(ChromiumStrategy::class);
});

it('auto strategy prefers imagick then chromium', function () {
    $generator = new ThumbnailGenerator(strategy: 'auto');
    $strategy = $generator->getStrategy();

    if (extension_loaded('imagick')) {
        expect($strategy)->toBeInstanceOf(ImagickStrategy::class);
    } else {
        expect($strategy)->toBeInstanceOf(ChromiumStrategy::class);
    }
});
