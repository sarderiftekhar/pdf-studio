<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfInspector;
use PdfStudio\Laravel\Manipulation\PdfPageCounter;
use PdfStudio\Laravel\Manipulation\PdfValidator;

it('returns invalid with no page count for non-pdf content', function () {
    $inspector = new PdfInspector(
        new class extends PdfValidator
        {
            public function isPdf(string $content): bool
            {
                expect($content)->toBe('not-a-pdf');

                return false;
            }
        },
        new class extends PdfPageCounter
        {
            public function count(string $pdfContent): int
            {
                throw new RuntimeException('count should not be called');
            }
        }
    );

    expect($inspector->inspect('not-a-pdf'))->toBe([
        'valid' => false,
        'page_count' => null,
        'byte_size' => strlen('not-a-pdf'),
    ]);
});

it('returns valid with page count for pdf content', function () {
    $inspector = new PdfInspector(
        new class extends PdfValidator
        {
            public function isPdf(string $content): bool
            {
                expect($content)->toBe('%PDF-fake');

                return true;
            }
        },
        new class extends PdfPageCounter
        {
            public function count(string $pdfContent): int
            {
                expect($pdfContent)->toBe('%PDF-fake');

                return 6;
            }
        }
    );

    expect($inspector->inspect('%PDF-fake'))->toBe([
        'valid' => true,
        'page_count' => 6,
        'byte_size' => strlen('%PDF-fake'),
    ]);
});

it('returns valid with null page count when page counting is unavailable', function () {
    $inspector = new PdfInspector(
        new class extends PdfValidator
        {
            public function isPdf(string $content): bool
            {
                return true;
            }
        },
        new class extends PdfPageCounter
        {
            public function count(string $pdfContent): int
            {
                throw new ManipulationException('fpdi missing');
            }
        }
    );

    expect($inspector->inspect('%PDF-fake'))->toBe([
        'valid' => true,
        'page_count' => null,
        'byte_size' => strlen('%PDF-fake'),
    ]);
});
