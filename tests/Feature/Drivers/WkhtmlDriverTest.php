<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\WkhtmlDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

it('implements RendererContract', function () {
    $driver = new WkhtmlDriver(['binary' => '/usr/local/bin/wkhtmltopdf']);

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('reports correct capabilities', function () {
    $driver = new WkhtmlDriver(['binary' => '/usr/local/bin/wkhtmltopdf']);
    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeTrue()
        ->and($caps->printBackground)->toBeTrue();
});

it('renders HTML to PDF bytes', function () {
    $binary = trim((string) shell_exec('which wkhtmltopdf 2>/dev/null'));

    if (empty($binary)) {
        $this->markTestSkipped('wkhtmltopdf binary not found');
    }

    $driver = new WkhtmlDriver(['binary' => $binary]);
    $options = new RenderOptions;

    $result = $driver->render('<html><body><h1>Wkhtml Test</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(strlen($result))->toBeGreaterThan(0)
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
});

it('throws when binary is not found', function () {
    $driver = new WkhtmlDriver(['binary' => '/nonexistent/wkhtmltopdf']);
    $options = new RenderOptions;

    $driver->render('<h1>Test</h1>', $options);
})->throws(DriverException::class);
