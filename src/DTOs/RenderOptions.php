<?php

namespace PdfStudio\Laravel\DTOs;

class RenderOptions
{
    /**
     * @param  array<string, int>  $margins
     */
    public function __construct(
        public string $format = 'A4',
        public bool $landscape = false,
        public array $margins = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10],
        public bool $printBackground = true,
        public ?string $headerHtml = null,
        public ?string $footerHtml = null,
    ) {}
}
