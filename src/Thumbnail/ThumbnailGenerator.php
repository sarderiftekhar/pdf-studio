<?php

namespace PdfStudio\Laravel\Thumbnail;

use PdfStudio\Laravel\Contracts\ThumbnailContract;

class ThumbnailGenerator
{
    protected ThumbnailContract $strategy;

    public function __construct(string $strategy = 'auto')
    {
        $this->strategy = $this->resolveStrategy($strategy);
    }

    public function generate(string $pdfContent, int $page = 1, int $width = 300, string $format = 'png', int $quality = 85): ThumbnailResult
    {
        return $this->strategy->generate($pdfContent, $page, $width, $format, $quality);
    }

    public function getStrategy(): ThumbnailContract
    {
        return $this->strategy;
    }

    protected function resolveStrategy(string $strategy): ThumbnailContract
    {
        return match ($strategy) {
            'imagick' => new ImagickStrategy,
            'chromium' => new ChromiumStrategy,
            'auto' => extension_loaded('imagick') ? new ImagickStrategy : new ChromiumStrategy,
            default => new ImagickStrategy,
        };
    }
}
