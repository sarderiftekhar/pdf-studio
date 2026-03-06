<?php

use PdfStudio\Laravel\Output\PdfResult;

it('stores PDF content and metadata', function () {
    $result = new PdfResult(
        content: 'PDF_BYTES',
        driver: 'fake',
        renderTimeMs: 42.5,
    );

    expect($result->content())->toBe('PDF_BYTES')
        ->and($result->mimeType)->toBe('application/pdf')
        ->and($result->bytes)->toBe(9)
        ->and($result->driver)->toBe('fake')
        ->and($result->renderTimeMs)->toBe(42.5);
});

it('returns base64 encoded content', function () {
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    expect($result->base64())->toBe(base64_encode('PDF_BYTES'));
});
