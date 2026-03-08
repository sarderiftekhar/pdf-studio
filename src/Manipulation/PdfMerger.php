<?php

namespace PdfStudio\Laravel\Manipulation;

use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Contracts\MergerContract;
use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;
use setasign\Fpdi\Fpdi;

class PdfMerger implements MergerContract
{
    /**
     * @param  array<int, string|PdfResult|array{path: string, disk?: string, pages?: string}>  $sources
     */
    public function merge(array $sources): PdfResult
    {
        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'PDF merging requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }

        if (count($sources) < 2) {
            throw new ManipulationException('At least two PDF sources are required for merging.');
        }

        $fpdi = new Fpdi;
        $tempFiles = [];

        try {
            foreach ($sources as $source) {
                $this->addSource($fpdi, $source, $tempFiles);
            }

            $content = $fpdi->Output('S');

            return new PdfResult(
                content: $content,
                driver: 'fpdi-merger',
                renderTimeMs: 0,
            );
        } finally {
            foreach ($tempFiles as $tempFile) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * @param  string|PdfResult|array<string, mixed>  $source
     * @param  array<int, string>  $tempFiles
     */
    protected function addSource(Fpdi $fpdi, string|PdfResult|array $source, array &$tempFiles): void
    {
        $filePath = $this->resolveSourcePath($source, $tempFiles);
        $pages = null;

        if (is_array($source) && isset($source['pages'])) {
            $pages = $this->parsePageRange($source['pages']);
        }

        $pageCount = $fpdi->setSourceFile($filePath);
        $pagesToImport = $pages ?? range(1, $pageCount);

        foreach ($pagesToImport as $pageNumber) {
            if ($pageNumber < 1 || $pageNumber > $pageCount) {
                continue;
            }

            $templateId = $fpdi->importPage($pageNumber);
            $size = $fpdi->getTemplateSize($templateId);
            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);
        }
    }

    /**
     * @param  string|PdfResult|array<string, mixed>  $source
     * @param  array<int, string>  $tempFiles
     */
    protected function resolveSourcePath(string|PdfResult|array $source, array &$tempFiles): string
    {
        if ($source instanceof PdfResult) {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdfstudio_merge_');
            file_put_contents($tempFile, $source->content());
            $tempFiles[] = $tempFile;

            return $tempFile;
        }

        if (is_array($source)) {
            $disk = $source['disk'] ?? config('filesystems.default');

            return Storage::disk($disk)->path($source['path']);
        }

        // String: check if it's a file path or raw PDF bytes
        if (file_exists($source)) {
            return $source;
        }

        // Treat as raw bytes
        $tempFile = tempnam(sys_get_temp_dir(), 'pdfstudio_merge_');
        file_put_contents($tempFile, $source);
        $tempFiles[] = $tempFile;

        return $tempFile;
    }

    /**
     * Parse page range string like "1-3,5,7-9" into array of page numbers.
     *
     * @return array<int>
     */
    protected function parsePageRange(string $range): array
    {
        $pages = [];

        foreach (explode(',', $range) as $part) {
            $part = trim($part);

            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part, 2);
                $pages = array_merge($pages, range((int) $start, (int) $end));
            } else {
                $pages[] = (int) $part;
            }
        }

        return $pages;
    }

    public function output(): PdfResult
    {
        throw new ManipulationException('Use merge() to produce output.');
    }
}
