<?php

namespace PdfStudio\Laravel\Manipulation;

use mikehaertl\pdftk\Pdf;
use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;

class PdfFlattener
{
    public function flatten(string $pdfContent): PdfResult
    {
        $this->ensurePdftkAvailable();

        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_flatten_in_');

        if ($inputFile === false) {
            throw new ManipulationException('Failed to create a temporary input file for PDF flattening.');
        }

        file_put_contents($inputFile, $pdfContent);

        try {
            return $this->flattenFile($inputFile);
        } finally {
            @unlink($inputFile);
        }
    }

    protected function flattenFile(string $inputFile): PdfResult
    {
        $pdf = new Pdf($inputFile);
        $pdf->flatten();

        $outputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_flatten_out_');

        if ($outputFile === false) {
            throw new ManipulationException('Failed to create a temporary output file for PDF flattening.');
        }

        try {
            if (!$pdf->saveAs($outputFile)) {
                throw new ManipulationException('pdftk flatten failed: '.($pdf->getError() ?? 'Unknown error'));
            }

            $content = file_get_contents($outputFile);

            if ($content === false) {
                throw new ManipulationException('Failed to read pdftk flattened output file.');
            }

            return new PdfResult(
                content: $content,
                driver: 'pdftk-flattener',
                renderTimeMs: 0,
            );
        } finally {
            @unlink($outputFile);
        }
    }

    protected function ensurePdftkAvailable(): void
    {
        if (!class_exists(Pdf::class)) {
            throw new ManipulationException(
                'PDF flattening requires mikehaertl/php-pdftk. Install it with: composer require mikehaertl/php-pdftk'
            );
        }
    }
}
