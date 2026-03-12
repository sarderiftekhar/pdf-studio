<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;

class PdfInspector
{
    public function __construct(
        protected ?PdfValidator $validator = null,
        protected ?PdfPageCounter $pageCounter = null,
    ) {
        $this->validator ??= new PdfValidator;
        $this->pageCounter ??= new PdfPageCounter;
    }

    /**
     * @return array{valid: bool, page_count: int|null, byte_size: int}
     */
    public function inspect(string $content): array
    {
        $valid = $this->validator->isPdf($content);
        $byteSize = strlen($content);

        if (!$valid) {
            return [
                'valid' => false,
                'page_count' => null,
                'byte_size' => $byteSize,
            ];
        }

        try {
            $pageCount = $this->pageCounter->count($content);
        } catch (ManipulationException) {
            $pageCount = null;
        }

        return [
            'valid' => true,
            'page_count' => $pageCount,
            'byte_size' => $byteSize,
        ];
    }
}
