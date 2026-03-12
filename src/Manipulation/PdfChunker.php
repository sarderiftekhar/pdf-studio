<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;
use setasign\Fpdi\Fpdi;

class PdfChunker
{
    public function __construct(
        protected ?PdfSplitter $splitter = null,
        protected ?PdfPageCounter $pageCounter = null,
    ) {
        $this->splitter ??= new PdfSplitter;
        $this->pageCounter ??= new PdfPageCounter;
    }

    /**
     * @return array<int, PdfResult>
     */
    public function chunk(string $pdfContent, int $pagesPerChunk): array
    {
        if ($pagesPerChunk < 1) {
            throw new ManipulationException('PDF chunking requires at least one page per chunk.');
        }

        $pageCount = $this->pageCount($pdfContent);
        $ranges = [];

        for ($start = 1; $start <= $pageCount; $start += $pagesPerChunk) {
            $end = min($start + $pagesPerChunk - 1, $pageCount);
            $ranges[] = "{$start}-{$end}";
        }

        return $this->splitter->split($pdfContent, $ranges);
    }

    protected function pageCount(string $pdfContent): int
    {
        return $this->pageCounter->count($pdfContent);
    }
}
