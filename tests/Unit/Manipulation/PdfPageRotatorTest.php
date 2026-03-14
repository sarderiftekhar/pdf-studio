<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfPageRotator;
use PdfStudio\Laravel\Output\PdfResult;

it('throws when rotation degrees are unsupported', function () {
    $rotator = new PdfPageRotator;

    $rotator->rotate('%PDF-fake', 45);
})->throws(ManipulationException::class, 'supports only 90, 180, or 270');

it('throws when targeted rotation pages are empty', function () {
    $rotator = new PdfPageRotator;

    $rotator->rotate('%PDF-fake', 90, []);
})->throws(ManipulationException::class, 'At least one page is required');

it('returns a PdfResult when rotation is stubbed', function () {
    $rotator = new class extends PdfPageRotator
    {
        public function rotate(string $pdfContent, int $degrees, ?array $pages = null): PdfResult
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($degrees)->toBe(90);
            expect($pages)->toBe([1, 3]);

            return new PdfResult(
                content: 'ROTATED_PDF',
                driver: 'fpdi-page-rotator',
                renderTimeMs: 0,
            );
        }
    };

    $result = $rotator->rotate('%PDF-fake', 90, [1, 3]);

    expect($result->content())->toBe('ROTATED_PDF');
});
