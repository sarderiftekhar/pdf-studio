<?php

namespace PdfStudio\Laravel\Output;

class StorageResult
{
    public function __construct(
        public string $path,
        public string $disk,
        public int $bytes,
        public string $mimeType = 'application/pdf',
    ) {}
}
