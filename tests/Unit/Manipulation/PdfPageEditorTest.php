<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfPageEditor;
use PdfStudio\Laravel\Output\PdfResult;

it('throws when no pages are provided for reordering', function () {
    $editor = new PdfPageEditor;

    $editor->reorder('%PDF-fake', []);
})->throws(ManipulationException::class, 'At least one page is required for PDF reordering');

it('throws when no pages are provided for removal', function () {
    $editor = new PdfPageEditor;

    $editor->remove('%PDF-fake', []);
})->throws(ManipulationException::class, 'At least one page is required for PDF page removal');

it('normalizes page lists for editing', function () {
    $editor = new PdfPageEditor;
    $reflection = new ReflectionMethod($editor, 'normalizePages');

    expect($reflection->invoke($editor, [3, '2', 'ignore', 1]))->toBe([3, 2, 1]);
});

it('returns a PdfResult when reordering is stubbed', function () {
    $editor = new class extends PdfPageEditor
    {
        public function reorder(string $pdfContent, array $pages): PdfResult
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pages)->toBe([3, 1, 2]);

            return new PdfResult(
                content: 'REORDERED_PDF',
                driver: 'fpdi-page-editor',
                renderTimeMs: 0,
            );
        }
    };

    $result = $editor->reorder('%PDF-fake', [3, 1, 2]);

    expect($result->content())->toBe('REORDERED_PDF');
});

it('returns a PdfResult when page removal is stubbed', function () {
    $editor = new class extends PdfPageEditor
    {
        public function remove(string $pdfContent, array $pages): PdfResult
        {
            expect($pdfContent)->toBe('%PDF-fake');
            expect($pages)->toBe([2, 4]);

            return new PdfResult(
                content: 'TRIMMED_PDF',
                driver: 'fpdi-page-editor',
                renderTimeMs: 0,
            );
        }
    };

    $result = $editor->remove('%PDF-fake', [2, 4]);

    expect($result->content())->toBe('TRIMMED_PDF');
});
