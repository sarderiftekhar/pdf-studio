<?php

namespace PdfStudio\Laravel\DTOs;

class TocEntry
{
    public function __construct(
        public int $level,
        public string $text,
        public int $pageNumber,
        public string $anchorId,
    ) {}
}
