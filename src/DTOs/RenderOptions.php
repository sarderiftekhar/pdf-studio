<?php

namespace PdfStudio\Laravel\DTOs;

class RenderOptions
{
    /**
     * @param  array<string, int>  $margins
     * @param  array<int>  $headerExcludePages
     * @param  array<int>  $footerExcludePages
     * @param  array<int>|null  $headerOnPages
     * @param  array<string>  $permissions
     * @param  array<string, scalar|null>  $metadata
     * @param  array<int, array{name: string, content?: string|null, path?: string|null, mime?: string|null}>  $attachments
     */
    public function __construct(
        public string $format = 'A4',
        public bool $landscape = false,
        public array $margins = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10],
        public bool $printBackground = true,
        public ?string $pageRanges = null,
        public bool $preferCssPageSize = false,
        public float $scale = 1.0,
        public bool $waitForFonts = false,
        public ?string $waitUntil = null,
        public ?int $waitDelayMs = null,
        public ?string $waitForSelector = null,
        public array $waitForSelectorOptions = [],
        public ?string $waitForFunction = null,
        public int $waitForFunctionTimeout = 0,
        public bool $taggedPdf = false,
        public bool $outline = false,
        public ?string $headerHtml = null,
        public ?string $footerHtml = null,
        public bool $autoHeight = false,
        public int $maxHeight = 5000,
        public array $headerExcludePages = [],
        public array $footerExcludePages = [],
        public bool $headerExceptFirst = false,
        public bool $footerExceptLast = false,
        public ?array $headerOnPages = null,
        public ?int $cacheTtl = null,
        public bool $noCache = false,
        public ?WatermarkOptions $watermark = null,
        public ?string $userPassword = null,
        public ?string $ownerPassword = null,
        public array $permissions = [],
        public array $metadata = [],
        public array $attachments = [],
        public ?string $pdfVariant = null,
        public ?TocOptions $tocOptions = null,
    ) {}
}
