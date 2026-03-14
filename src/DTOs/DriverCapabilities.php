<?php

namespace PdfStudio\Laravel\DTOs;

class DriverCapabilities
{
    /**
     * @param  array<int, string>  $supportedFormats
     */
    public function __construct(
        public bool $landscape = true,
        public bool $customMargins = true,
        public bool $headerFooter = false,
        public bool $printBackground = true,
        public array $supportedFormats = ['A4', 'Letter', 'Legal'],
        public bool $autoHeight = false,
        public bool $pageRanges = false,
        public bool $preferCssPageSize = false,
        public bool $scale = false,
        public bool $waitForFonts = false,
        public bool $waitUntil = false,
        public bool $waitDelay = false,
        public bool $waitForSelector = false,
        public bool $waitForFunction = false,
        public bool $taggedPdf = false,
        public bool $outline = false,
        public bool $metadata = false,
        public bool $attachments = false,
        public bool $pdfVariants = false,
    ) {}
}
