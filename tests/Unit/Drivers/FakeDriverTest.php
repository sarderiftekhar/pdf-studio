<?php

use PdfStudio\Laravel\Drivers\FakeDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

it('renders HTML to deterministic output', function () {
    $driver = new FakeDriver;
    $options = new RenderOptions;

    $result = $driver->render('<h1>Hello</h1>', $options);

    expect($result)->toBeString()
        ->and($result)->toContain('FAKE_PDF');
});

it('includes the HTML content in output for assertion', function () {
    $driver = new FakeDriver;
    $options = new RenderOptions;

    $result = $driver->render('<h1>Invoice</h1>', $options);

    expect($result)->toContain('<h1>Invoice</h1>');
});

it('reports full capabilities', function () {
    $driver = new FakeDriver;

    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->landscape)->toBeTrue()
        ->and($caps->headerFooter)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue();
});
