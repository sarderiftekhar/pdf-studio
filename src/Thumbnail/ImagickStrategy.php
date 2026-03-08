<?php

namespace PdfStudio\Laravel\Thumbnail;

use PdfStudio\Laravel\Contracts\ThumbnailContract;
use PdfStudio\Laravel\Exceptions\RenderException;

class ImagickStrategy implements ThumbnailContract
{
    public function generate(string $pdfContent, int $page = 1, int $width = 300, string $format = 'png', int $quality = 85): ThumbnailResult
    {
        if (!extension_loaded('imagick')) {
            throw new RenderException('The imagick PHP extension is required for PDF thumbnail generation.');
        }

        $imagick = new \Imagick;
        $imagick->setResolution(150, 150);
        $imagick->readImageBlob($pdfContent);

        $pageIndex = max(0, $page - 1);
        if ($pageIndex >= $imagick->getNumberImages()) {
            throw new RenderException("Page {$page} does not exist in the PDF.");
        }

        $imagick->setIteratorIndex($pageIndex);
        $imagick->setImageBackgroundColor('white');
        $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

        $originalWidth = $imagick->getImageWidth();
        $originalHeight = $imagick->getImageHeight();
        $height = (int) round($width * ($originalHeight / $originalWidth));

        $imagick->thumbnailImage($width, $height);
        $imagick->setImageFormat($format);

        if (in_array($format, ['jpeg', 'jpg', 'webp'])) {
            $imagick->setImageCompressionQuality($quality);
        }

        $content = $imagick->getImageBlob();
        $mimeType = $this->mimeType($format);

        $imagick->clear();
        $imagick->destroy();

        return new ThumbnailResult($content, $mimeType, $width, $height);
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
