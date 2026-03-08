<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\Thumbnail\ThumbnailResult;

interface ThumbnailContract
{
    public function generate(string $pdfContent, int $page = 1, int $width = 300, string $format = 'png', int $quality = 85): ThumbnailResult;
}
