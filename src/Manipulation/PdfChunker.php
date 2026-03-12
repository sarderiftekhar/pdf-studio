<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;
use setasign\Fpdi\Fpdi;

class PdfChunker
{
    public function __construct(
        protected ?PdfSplitter $splitter = null,
    ) {
        $this->splitter ??= new PdfSplitter;
    }

    /**
     * @return array<int, PdfResult>
     */
    public function chunk(string $pdfContent, int $pagesPerChunk): array
    {
        if ($pagesPerChunk < 1) {
            throw new ManipulationException('PDF chunking requires at least one page per chunk.');
        }

        $this->ensureFpdiAvailable();

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
        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_chunk_');

        if ($inputFile === false) {
            throw new ManipulationException('Failed to create a temporary file for PDF chunking.');
        }

        file_put_contents($inputFile, $pdfContent);

        try {
            $fpdi = new Fpdi;

            return $fpdi->setSourceFile($inputFile);
        } finally {
            @unlink($inputFile);
        }
    }

    protected function ensureFpdiAvailable(): void
    {
        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'PDF chunking requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }
    }
}
