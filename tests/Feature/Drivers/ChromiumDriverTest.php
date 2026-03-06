<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\ChromiumDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

it('implements RendererContract', function () {
    $driver = new ChromiumDriver;

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('reports correct capabilities', function () {
    $driver = new ChromiumDriver;
    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeTrue()
        ->and($caps->printBackground)->toBeTrue()
        ->and($caps->supportedFormats)->toContain('A4')
        ->and($caps->supportedFormats)->toContain('Letter');
});

it('renders HTML to PDF bytes', function () {
    $driver = new ChromiumDriver;
    $options = new RenderOptions;

    $result = $driver->render('<html><body><h1>Chromium Test</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(strlen($result))->toBeGreaterThan(0)
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
})->skip(!class_exists(\Spatie\Browsershot\Browsershot::class), 'Browsershot not installed');

it('respects landscape option', function () {
    $driver = new ChromiumDriver;
    $options = new RenderOptions(landscape: true);

    $result = $driver->render('<html><body><h1>Landscape</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
})->skip(!class_exists(\Spatie\Browsershot\Browsershot::class), 'Browsershot not installed');

it('respects format option', function () {
    $driver = new ChromiumDriver;
    $options = new RenderOptions(format: 'Letter');

    $result = $driver->render('<html><body><h1>Letter</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
})->skip(!class_exists(\Spatie\Browsershot\Browsershot::class), 'Browsershot not installed');
