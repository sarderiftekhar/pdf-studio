<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;

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

    public function assertPdf(string $content, string $label = 'content'): void
    {
        if (!$this->isPdf($content)) {
            throw new ManipulationException("The provided {$label} is not a valid PDF payload.");
        }
    }
}
