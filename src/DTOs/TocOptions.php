<?php

namespace PdfStudio\Laravel\DTOs;

class TocOptions
{
    public function __construct(
        public int $depth = 6,
        public string $title = 'Table of Contents',
        public string $mode = 'auto',
        public bool $bookmarks = false,
    ) {}
}
