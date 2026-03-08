<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Output\PdfResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('returns a StreamedResponse for Livewire download', function () {
    $response = Pdf::html('<h1>Livewire</h1>')->livewireDownload('invoice.pdf');

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('invoice.pdf')
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('streams the PDF content', function () {
    $response = Pdf::html('<h1>Stream Test</h1>')->livewireDownload('test.pdf');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('FAKE_PDF');
});

it('toBase64 returns base64 encoded content', function () {
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    expect($result->toBase64())->toBe(base64_encode('PDF_BYTES'))
        ->and($result->toBase64())->toBe($result->base64());
});

it('has Content-Length header', function () {
    $response = Pdf::html('<h1>Test</h1>')->livewireDownload('doc.pdf');

    expect($response->headers->get('Content-Length'))->not->toBeNull();
});
