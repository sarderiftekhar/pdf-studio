<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;
use setasign\Fpdi\Fpdi;

class PdfSplitter
{
    /**
     * Split a PDF byte string into multiple PdfResult parts by page ranges.
     *
     * @param  array<int, string>  $ranges
     * @return array<int, PdfResult>
     */
    public function split(string $pdfContent, array $ranges): array
    {
        if ($ranges === []) {
            throw new ManipulationException('At least one page range is required for splitting.');
        }

        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'PDF splitting requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }

        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_split_');

        if ($inputFile === false) {
            throw new ManipulationException('Failed to create a temporary file for PDF splitting.');
        }

        file_put_contents($inputFile, $pdfContent);

        try {
            $results = [];

            foreach ($ranges as $range) {
                $pages = $this->parsePageRange($range);
                $results[] = $this->extractPages($inputFile, $pages);
            }

            return $results;
        } finally {
            @unlink($inputFile);
        }
    }

    /**
     * @param  array<int>  $pages
     */
    protected function extractPages(string $inputFile, array $pages): PdfResult
    {
        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($inputFile);

        foreach ($pages as $pageNumber) {
            if ($pageNumber < 1 || $pageNumber > $pageCount) {
                continue;
            }

            $templateId = $fpdi->importPage($pageNumber);
            $size = $fpdi->getTemplateSize($templateId);
            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);
        }

        $content = $fpdi->Output('S');

        return new PdfResult(
            content: $content,
            driver: 'fpdi-splitter',
            renderTimeMs: 0,
        );
    }

    /**
     * @return array<int>
     */
    protected function parsePageRange(string $range): array
    {
        $pages = [];

        foreach (explode(',', $range) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part, 2);
                $pages = array_merge($pages, range((int) $start, (int) $end));
            } else {
                $pages[] = (int) $part;
            }
        }

        return $pages;
    }
}
