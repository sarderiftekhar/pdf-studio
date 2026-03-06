<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'dompdf');
});

it('generates a real PDF via dompdf through the full pipeline', function () {
    $result = Pdf::html('<html><body><h1>Hello PDF</h1><p>This is a real PDF.</p></body></html>')
        ->format('A4')
        ->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->driver)->toBe('dompdf')
        ->and($result->content())->toStartWith('%PDF')
        ->and($result->bytes)->toBeGreaterThan(100)
        ->and($result->renderTimeMs)->toBeGreaterThanOrEqual(0);
});

it('generates a landscape PDF via dompdf', function () {
    $result = Pdf::html('<html><body><h1>Landscape</h1></body></html>')
        ->landscape()
        ->render();

    expect($result->content())->toStartWith('%PDF');
});

it('generates a PDF with custom format via dompdf', function () {
    $result = Pdf::html('<html><body><h1>Letter</h1></body></html>')
        ->format('Letter')
        ->render();

    expect($result->content())->toStartWith('%PDF');
});

it('downloads a real PDF', function () {
    $response = Pdf::html('<html><body><h1>Download</h1></body></html>')
        ->download('test.pdf');

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('test.pdf')
        ->and(str_starts_with($response->getContent(), '%PDF'))->toBeTrue();
});

it('saves a real PDF to storage', function () {
    \Illuminate\Support\Facades\Storage::fake('local');

    $storageResult = Pdf::html('<html><body><h1>Save</h1></body></html>')
        ->save('output.pdf', 'local');

    expect($storageResult)->toBeInstanceOf(StorageResult::class)
        ->and($storageResult->bytes)->toBeGreaterThan(100);
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists('output.pdf');
});
