<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;
use setasign\Fpdi\Fpdi;

class PdfPageEditor
{
    /**
     * @param  array<int, int>  $pages
     */
    public function reorder(string $pdfContent, array $pages): PdfResult
    {
        if ($pages === []) {
            throw new ManipulationException('At least one page is required for PDF reordering.');
        }

        $normalizedPages = $this->normalizePages($pages);

        return $this->edit($pdfContent, function (int $pageCount) use ($normalizedPages): array {
            return array_values(array_filter(
                $normalizedPages,
                static fn (int $page): bool => $page >= 1 && $page <= $pageCount
            ));
        }, 'fpdi-page-editor');
    }

    /**
     * @param  array<int, int>  $pages
     */
    public function remove(string $pdfContent, array $pages): PdfResult
    {
        if ($pages === []) {
            throw new ManipulationException('At least one page is required for PDF page removal.');
        }

        $pagesToRemove = $this->normalizePages($pages);

        return $this->edit($pdfContent, function (int $pageCount) use ($pagesToRemove): array {
            $removeLookup = array_fill_keys($pagesToRemove, true);
            $remaining = [];

            for ($page = 1; $page <= $pageCount; $page++) {
                if (!isset($removeLookup[$page])) {
                    $remaining[] = $page;
                }
            }

            return $remaining;
        }, 'fpdi-page-editor');
    }

    /**
     * @param  callable(int): array<int, int>  $pageResolver
     */
    protected function edit(string $pdfContent, callable $pageResolver, string $driver): PdfResult
    {
        $this->ensureFpdiAvailable();

        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_page_edit_');

        if ($inputFile === false) {
            throw new ManipulationException('Failed to create a temporary file for PDF page editing.');
        }

        file_put_contents($inputFile, $pdfContent);

        try {
            $fpdi = new Fpdi;
            $pageCount = $fpdi->setSourceFile($inputFile);
            $pages = $pageResolver($pageCount);

            if ($pages === []) {
                throw new ManipulationException('PDF page editing would produce an empty document.');
            }

            foreach ($pages as $pageNumber) {
                $templateId = $fpdi->importPage($pageNumber);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }

            return new PdfResult(
                content: $fpdi->Output('S'),
                driver: $driver,
                renderTimeMs: 0,
            );
        } finally {
            @unlink($inputFile);
        }
    }

    /**
     * @param  array<int, int>  $pages
     * @return array<int, int>
     */
    protected function normalizePages(array $pages): array
    {
        return array_values(array_map(
            static fn (int $page): int => (int) $page,
            array_filter($pages, static fn ($page): bool => is_int($page) || ctype_digit((string) $page))
        ));
    }

    protected function ensureFpdiAvailable(): void
    {
        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'PDF page editing requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }
    }
}
