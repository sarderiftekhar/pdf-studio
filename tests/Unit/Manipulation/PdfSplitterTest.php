<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfSplitter;
use PdfStudio\Laravel\Output\PdfResult;

it('throws when no ranges are provided', function () {
    $splitter = new PdfSplitter;

    $splitter->split('fake-pdf', []);
})->throws(ManipulationException::class, 'At least one page range');

it('parses page ranges into page numbers', function () {
    $splitter = new PdfSplitter;
    $reflection = new ReflectionMethod($splitter, 'parsePageRange');

    $result = $reflection->invoke($splitter, '1-3,5,7-8');

    expect($result)->toBe([1, 2, 3, 5, 7, 8]);
});

it('returns an array of PdfResult objects when extraction is stubbed', function () {
    $splitter = new class extends PdfSplitter
    {
        public function split(string $pdfContent, array $ranges): array
        {
            $results = [];

            foreach ($ranges as $index => $range) {
                $results[] = new PdfResult(
                    content: "FAKE_SPLIT_{$index}_{$range}",
                    driver: 'fpdi-splitter',
                    renderTimeMs: 0,
                );
            }

            return $results;
        }
    };

    $results = $splitter->split('%PDF-fake', ['1-2', '3-4']);

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBeInstanceOf(PdfResult::class)
        ->and($results[0]->content())->toBe('FAKE_SPLIT_0_1-2')
        ->and($results[1]->content())->toBe('FAKE_SPLIT_1_3-4');
});
