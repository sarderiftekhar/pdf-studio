<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfFlattener;
use PdfStudio\Laravel\Output\PdfResult;

it('throws when pdftk is not available', function () {
    $flattener = new PdfFlattener;

    $flattener->flatten('%PDF-fake');
})->throws(ManipulationException::class, 'PDF flattening requires');

it('returns a PdfResult when flattening is stubbed', function () {
    $flattener = new class extends PdfFlattener
    {
        public function flatten(string $pdfContent): PdfResult
        {
            return new PdfResult(
                content: 'FLATTENED_PDF',
                driver: 'pdftk-flattener',
                renderTimeMs: 0,
            );
        }
    };

    $result = $flattener->flatten('%PDF-fake');

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('FLATTENED_PDF')
        ->and($result->driver)->toBe('pdftk-flattener');
});
