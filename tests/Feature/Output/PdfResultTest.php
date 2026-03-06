<?php

use Illuminate\Http\Response;
use PdfStudio\Laravel\Output\PdfResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

it('returns a download response', function () {
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    $response = $result->download('test.pdf');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('test.pdf');
});

it('returns a stream response', function () {
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    $response = $result->stream('preview.pdf');

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->headers->get('Content-Disposition'))->toContain('preview.pdf');
});
