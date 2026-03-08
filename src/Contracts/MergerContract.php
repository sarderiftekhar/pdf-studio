<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\Output\PdfResult;

interface MergerContract
{
    /**
     * @param  array<int, string|PdfResult|array{path: string, disk?: string, pages?: string}>  $sources
     */
    public function merge(array $sources): PdfResult;
}
