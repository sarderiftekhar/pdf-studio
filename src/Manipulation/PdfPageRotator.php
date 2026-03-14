<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;
use setasign\Fpdi\Fpdi;

class PdfPageRotator
{
    /**
     * @param  array<int, int>|null  $pages
     */
    public function rotate(string $pdfContent, int $degrees, ?array $pages = null): PdfResult
    {
        $normalizedDegrees = $this->normalizeDegrees($degrees);
        $targetPages = $this->normalizePages($pages);

        $this->ensureFpdiAvailable();

        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_rotate_');

        if ($inputFile === false) {
            throw new ManipulationException('Failed to create a temporary file for PDF page rotation.');
        }

        file_put_contents($inputFile, $pdfContent);

        try {
            $pdf = $this->createRotatingFpdi();
            $pageCount = $pdf->setSourceFile($inputFile);
            $pageLookup = $targetPages === null ? null : array_fill_keys($targetPages, true);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);
                $applyRotation = $pageLookup === null || isset($pageLookup[$pageNumber]);

                $pageWidth = $size['width'];
                $pageHeight = $size['height'];

                if ($applyRotation && in_array($normalizedDegrees, [90, 270], true)) {
                    [$pageWidth, $pageHeight] = [$pageHeight, $pageWidth];
                }

                $pdf->AddPage($size['orientation'], [$pageWidth, $pageHeight]);

                if ($applyRotation) {
                    $this->useRotatedTemplate($pdf, $templateId, $size['width'], $size['height'], $normalizedDegrees);
                } else {
                    $pdf->useTemplate($templateId);
                }
            }

            return new PdfResult(
                content: $pdf->Output('S'),
                driver: 'fpdi-page-rotator',
                renderTimeMs: 0,
            );
        } finally {
            @unlink($inputFile);
        }
    }

    protected function normalizeDegrees(int $degrees): int
    {
        $normalized = (($degrees % 360) + 360) % 360;

        if (!in_array($normalized, [90, 180, 270], true)) {
            throw new ManipulationException('PDF page rotation supports only 90, 180, or 270 degrees.');
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>|null  $pages
     * @return array<int, int>|null
     */
    protected function normalizePages(?array $pages): ?array
    {
        if ($pages === null) {
            return null;
        }

        if ($pages === []) {
            throw new ManipulationException('At least one page is required when targeting specific pages for rotation.');
        }

        return array_values(array_map(
            static fn (int $page): int => (int) $page,
            array_filter($pages, static fn ($page): bool => is_int($page) || ctype_digit((string) $page))
        ));
    }

    protected function useRotatedTemplate(object $pdf, int $templateId, float $width, float $height, int $degrees): void
    {
        if ($degrees === 90) {
            $pdf->Rotate(90, 0, 0);
            $pdf->useTemplate($templateId, 0, -$height, $width, $height);
            $pdf->Rotate(0);

            return;
        }

        if ($degrees === 180) {
            $pdf->Rotate(180, $width / 2, $height / 2);
            $pdf->useTemplate($templateId, 0, 0, $width, $height);
            $pdf->Rotate(0);

            return;
        }

        $pdf->Rotate(270, 0, 0);
        $pdf->useTemplate($templateId, -$width, 0, $width, $height);
        $pdf->Rotate(0);
    }

    protected function ensureFpdiAvailable(): void
    {
        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'PDF page rotation requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }
    }

    protected function createRotatingFpdi(): object
    {
        return new class extends Fpdi
        {
            protected int $angle = 0;

            public function Rotate(int $angle, float $x = -1, float $y = -1): void
            {
                if ($x === -1) {
                    $x = $this->x;
                }
                if ($y === -1) {
                    $y = $this->y;
                }

                if ($this->angle !== 0) {
                    $this->_out('Q');
                }

                $this->angle = $angle;

                if ($angle !== 0) {
                    $angleInRadians = $angle * M_PI / 180;
                    $c = cos($angleInRadians);
                    $s = sin($angleInRadians);
                    $cx = $x * $this->k;
                    $cy = ($this->h - $y) * $this->k;

                    $this->_out(sprintf(
                        'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
                        $c,
                        $s,
                        -$s,
                        $c,
                        $cx,
                        $cy,
                        -$cx,
                        -$cy
                    ));
                }
            }

            protected function _endpage(): void
            {
                if ($this->angle !== 0) {
                    $this->angle = 0;
                    $this->_out('Q');
                }

                parent::_endpage();
            }
        };
    }
}
