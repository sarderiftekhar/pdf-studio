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
     */
    public function __construct(
        public string $format = 'A4',
        public bool $landscape = false,
        public array $margins = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10],
        public bool $printBackground = true,
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
    ) {}
}
