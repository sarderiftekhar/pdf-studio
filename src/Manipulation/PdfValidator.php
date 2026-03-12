<?php

namespace PdfStudio\Laravel\Manipulation;

class PdfValidator
{
    public function isPdf(string $content): bool
    {
        $trimmed = ltrim($content);

        if ($trimmed === '' || !str_starts_with($trimmed, '%PDF-')) {
            return false;
        }

        return str_contains($trimmed, '%%EOF');
    }
}
