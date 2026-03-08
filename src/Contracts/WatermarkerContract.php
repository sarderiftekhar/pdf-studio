<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\DTOs\WatermarkOptions;

interface WatermarkerContract
{
    public function apply(string $pdfContent, WatermarkOptions $options): string;
}
