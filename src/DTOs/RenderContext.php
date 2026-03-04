<?php

namespace PdfStudio\Laravel\DTOs;

class RenderContext
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public ?string $viewName = null,
        public ?string $rawHtml = null,
        public array $data = [],
        public ?string $compiledHtml = null,
        public ?string $compiledCss = null,
        public ?string $styledHtml = null,
        public ?string $pdfContent = null,
        public ?RenderOptions $options = null,
    ) {
        $this->options ??= new RenderOptions();
    }
}
