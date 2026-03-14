<?php

namespace PdfStudio\Laravel\Thumbnail;

use PdfStudio\Laravel\Contracts\ThumbnailContract;
use PdfStudio\Laravel\Exceptions\RenderException;

class ChromiumStrategy implements ThumbnailContract
{
    public function generate(string $pdfContent, int $page = 1, int $width = 300, string $format = 'png', int $quality = 85): ThumbnailResult
    {
        if (!class_exists(\Spatie\Browsershot\Browsershot::class)) {
            throw new RenderException(
                'The spatie/browsershot package is required for Chromium-based thumbnails. '
                .'Install it with: composer require spatie/browsershot'
            );
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pdfstudio_thumb_');
        file_put_contents($tempFile, $pdfContent);

        try {
            $screenshot = \Spatie\Browsershot\Browsershot::url('file://'.$tempFile)
                ->windowSize($width, (int) round($width * 1.414))
                ->setScreenshotType($format === 'jpg' ? 'jpeg' : $format, $quality)
                ->screenshot();

            $imageInfo = getimagesizefromstring($screenshot);
            $actualWidth = $imageInfo[0] ?? $width;
            $actualHeight = $imageInfo[1] ?? (int) round($width * 1.414);

            return new ThumbnailResult(
                $screenshot,
                $this->mimeType($format),
                $actualWidth,
                $actualHeight,
            );
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    protected function mimeType(string $format): string
    {
        return match ($format) {
            'png' => 'image/png',
            'jpeg', 'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
