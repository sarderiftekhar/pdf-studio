<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\DompdfDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

it('implements RendererContract', function () {
    $driver = new DompdfDriver;

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('reports correct capabilities', function () {
    $driver = new DompdfDriver;
    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeFalse()
        ->and($caps->printBackground)->toBeFalse();
});

it('renders HTML to PDF bytes', function () {
    $driver = new DompdfDriver;
    $options = new RenderOptions;

    $result = $driver->render('<html><body><h1>Dompdf Test</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(strlen($result))->toBeGreaterThan(0)
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
});

it('respects landscape option', function () {
    $driver = new DompdfDriver;
    $options = new RenderOptions(landscape: true);

    $result = $driver->render('<html><body><h1>Landscape</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
});

it('respects format option', function () {
    $driver = new DompdfDriver;
    $options = new RenderOptions(format: 'Letter');

    $result = $driver->render('<html><body><h1>Letter</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
});
