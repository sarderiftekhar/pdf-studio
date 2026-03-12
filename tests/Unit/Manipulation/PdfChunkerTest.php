<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfChunker;
use PdfStudio\Laravel\Manipulation\PdfPageCounter;
use PdfStudio\Laravel\Manipulation\PdfSplitter;
use PdfStudio\Laravel\Output\PdfResult;

it('throws when chunk size is invalid', function () {
    $chunker = new PdfChunker(new class extends PdfSplitter {}, new class extends PdfPageCounter {});

    $chunker->chunk('%PDF-fake', 0);
})->throws(ManipulationException::class, 'at least one page per chunk');

it('delegates computed page ranges to the splitter', function () {
    $splitter = new class extends PdfSplitter
    {
        /** @var array<int, string> */
        public array $capturedRanges = [];

        public function split(string $pdfContent, array $ranges): array
        {
            $this->capturedRanges = $ranges;

            return array_map(
                static fn (string $range): PdfResult => new PdfResult(
                    content: "CHUNK_{$range}",
                    driver: 'fpdi-splitter',
                    renderTimeMs: 0,
                ),
                $ranges
            );
        }
    };

    $pageCounter = new class extends PdfPageCounter
    {
        public function count(string $pdfContent): int
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return 7;
        }
    };

    $chunker = new PdfChunker($splitter, $pageCounter);

    $results = $chunker->chunk('%PDF-fake', 3);

    expect($splitter->capturedRanges)->toBe(['1-3', '4-6', '7-7'])
        ->and($results)->toHaveCount(3)
        ->and($results[0]->content())->toBe('CHUNK_1-3')
        ->and($results[2]->content())->toBe('CHUNK_7-7');
});

it('returns computed chunk ranges without splitting', function () {
    $pageCounter = new class extends PdfPageCounter
    {
        public function count(string $pdfContent): int
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return 8;
        }
    };

    $splitter = new class extends PdfSplitter
    {
        public function split(string $pdfContent, array $ranges): array
        {
            throw new RuntimeException('split should not be called');
        }
    };

    $chunker = new PdfChunker($splitter, $pageCounter);

    expect($chunker->chunkRanges('%PDF-fake', 3))->toBe(['1-3', '4-6', '7-8']);
});

it('returns a structured chunk plan without splitting', function () {
    $pageCounter = new class extends PdfPageCounter
    {
        public function count(string $pdfContent): int
        {
            expect($pdfContent)->toBe('%PDF-fake');

            return 8;
        }
    };

    $chunker = new PdfChunker(new class extends PdfSplitter {}, $pageCounter);

    expect($chunker->chunkPlan('%PDF-fake', 3))->toBe([
        ['index' => 1, 'start' => 1, 'end' => 3, 'pages' => 3, 'range' => '1-3'],
        ['index' => 2, 'start' => 4, 'end' => 6, 'pages' => 3, 'range' => '4-6'],
        ['index' => 3, 'start' => 7, 'end' => 8, 'pages' => 2, 'range' => '7-8'],
    ]);
});
