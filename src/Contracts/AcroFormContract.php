<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\Output\PdfResult;

interface AcroFormContract
{
    /**
     * @param  array<string, string>  $fieldValues
     */
    public function fill(string $pdfPath, array $fieldValues, bool $flatten = false): PdfResult;

    /**
     * @return array<int, string>
     */
    public function fields(string $pdfPath): array;
}
