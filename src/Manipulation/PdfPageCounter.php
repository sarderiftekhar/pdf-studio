<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use setasign\Fpdi\Fpdi;

class PdfPageCounter
{
    public function count(string $pdfContent): int
    {
        $this->ensureFpdiAvailable();

        $inputFile = tempnam(sys_get_temp_dir(), 'pdfstudio_page_count_');

        if ($inputFile === false) {
            throw new ManipulationException('Failed to create a temporary file for PDF page counting.');
        }

        file_put_contents($inputFile, $pdfContent);

        try {
            return $this->countFromFile($inputFile);
        } finally {
            @unlink($inputFile);
        }
    }

    protected function countFromFile(string $inputFile): int
    {
        $fpdi = new Fpdi;

        return $fpdi->setSourceFile($inputFile);
    }

    protected function ensureFpdiAvailable(): void
    {
        if (!class_exists(Fpdi::class)) {
            throw new ManipulationException(
                'PDF page counting requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }
    }
}
