<?php

namespace PdfStudio\Laravel\Manipulation;

use mikehaertl\pdftk\Pdf;
use PdfStudio\Laravel\Contracts\AcroFormContract;
use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;

class AcroFormFiller implements AcroFormContract
{
    /**
     * @param  array<string, string>  $fieldValues
     */
    public function fill(string $pdfPath, array $fieldValues, bool $flatten = false): PdfResult
    {
        $this->ensurePdftkAvailable();

        $pdf = new Pdf($pdfPath);
        $pdf->fillForm($fieldValues);

        if ($flatten) {
            $pdf->flatten();
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pdfstudio_acroform_');

        if (!$pdf->saveAs($tempFile)) {
            throw new ManipulationException('pdftk form fill failed: '.($pdf->getError() ?? 'Unknown error'));
        }

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        if ($content === false) {
            throw new ManipulationException('Failed to read pdftk output file.');
        }

        return new PdfResult(
            content: $content,
            driver: 'pdftk-acroform',
            renderTimeMs: 0,
        );
    }

    /**
     * @return array<int, string>
     */
    public function fields(string $pdfPath): array
    {
        $this->ensurePdftkAvailable();

        $pdf = new Pdf($pdfPath);
        $data = $pdf->getDataFields();

        if ($data === false) {
            throw new ManipulationException('pdftk field extraction failed: '.($pdf->getError() ?? 'Unknown error'));
        }

        $fields = [];

        foreach ($data as $field) {
            if (isset($field['FieldName'])) {
                $fields[] = $field['FieldName'];
            }
        }

        return $fields;
    }

    public function output(): PdfResult
    {
        throw new ManipulationException('Use fill() to produce output.');
    }

    protected function ensurePdftkAvailable(): void
    {
        if (!class_exists(Pdf::class)) {
            throw new ManipulationException(
                'AcroForm filling requires mikehaertl/php-pdftk. Install it with: composer require mikehaertl/php-pdftk'
            );
        }
    }
}
