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
        ->and($caps->pageRanges)->toBeTrue()
        ->and($caps->preferCssPageSize)->toBeTrue()
        ->and($caps->scale)->toBeTrue()
        ->and($caps->waitForFonts)->toBeTrue()
        ->and($caps->waitUntil)->toBeTrue()
        ->and($caps->waitDelay)->toBeTrue()
        ->and($caps->waitForSelector)->toBeTrue()
        ->and($caps->waitForFunction)->toBeTrue()
        ->and($caps->taggedPdf)->toBeTrue()
        ->and($caps->outline)->toBeTrue()
        ->and($caps->supportedFormats)->toContain('A4')
        ->and($caps->supportedFormats)->toContain('Letter');
});

it('applies advanced PDF options to browsershot', function () {
    $driver = new class extends ChromiumDriver
    {
        public function browsershotForTest(string $html, RenderOptions $options): \Spatie\Browsershot\Browsershot
        {
            return $this->createBrowsershot($html, $options);
        }
    };

    $options = new RenderOptions(
        pageRanges: '1-3',
        preferCssPageSize: true,
        scale: 0.9,
        waitForFonts: true,
        waitUntil: 'networkidle2',
        waitDelayMs: 750,
        waitForSelector: '#ready',
        waitForSelectorOptions: ['visible' => true],
        waitForFunction: 'window.__PDF_READY === true',
        waitForFunctionTimeout: 5000,
        taggedPdf: true,
        outline: true,
    );

    $browsershot = $driver->browsershotForTest('<html><body><h1>Test</h1></body></html>', $options);

    $additionalOptions = (function () {
        $property = new ReflectionProperty($this, 'additionalOptions');
        $property->setAccessible(true);

        return $property->getValue($this);
    })->call($browsershot);

    $scale = (function () {
        $property = new ReflectionProperty($this, 'scale');
        $property->setAccessible(true);

        return $property->getValue($this);
    })->call($browsershot);

    $taggedPdf = (function () {
        $property = new ReflectionProperty($this, 'taggedPdf');
        $property->setAccessible(true);

        return $property->getValue($this);
    })->call($browsershot);

    expect($additionalOptions)->toMatchArray([
        'pageRanges' => '1-3',
        'preferCSSPageSize' => true,
        'waitForFonts' => true,
        'waitUntil' => 'networkidle2',
        'delay' => 750,
        'waitForSelector' => '#ready',
        'waitForSelectorOptions' => ['visible' => true],
        'function' => 'window.__PDF_READY === true',
        'functionTimeout' => 5000,
        'outline' => true,
    ])
        ->and($scale)->toBe(0.9)
        ->and($taggedPdf)->toBeTrue();
});

it('renders HTML to PDF bytes', function () {
    $driver = new ChromiumDriver;
    $options = new RenderOptions;

    $result = $driver->render('<html><body><h1>Chromium Test</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(strlen($result))->toBeGreaterThan(0)
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
})->skip(fn () => (bool) getenv('CI') || !class_exists(\Spatie\Browsershot\Browsershot::class), 'Chromium/Puppeteer not available');

it('respects landscape option', function () {
    $driver = new ChromiumDriver;
    $options = new RenderOptions(landscape: true);

    $result = $driver->render('<html><body><h1>Landscape</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
})->skip(fn () => (bool) getenv('CI') || !class_exists(\Spatie\Browsershot\Browsershot::class), 'Chromium/Puppeteer not available');

it('respects format option', function () {
    $driver = new ChromiumDriver;
    $options = new RenderOptions(format: 'Letter');

    $result = $driver->render('<html><body><h1>Letter</h1></body></html>', $options);

    expect($result)->toBeString()
        ->and(str_starts_with($result, '%PDF'))->toBeTrue();
})->skip(fn () => (bool) getenv('CI') || !class_exists(\Spatie\Browsershot\Browsershot::class), 'Chromium/Puppeteer not available');
