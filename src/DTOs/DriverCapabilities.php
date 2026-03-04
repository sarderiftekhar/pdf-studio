<?php

namespace PdfStudio\Laravel\DTOs;

class DriverCapabilities
{
    public function __construct(
        public bool $landscape = true,
        public bool $customMargins = true,
        public bool $headerFooter = false,
        public bool $printBackground = true,
        public array $supportedFormats = ['A4', 'Letter', 'Legal'],
    ) {}
}
