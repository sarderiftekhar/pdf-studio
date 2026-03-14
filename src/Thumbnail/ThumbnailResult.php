<?php

namespace PdfStudio\Laravel\Thumbnail;

use Illuminate\Support\Facades\Storage;

class ThumbnailResult
{
    public function __construct(
        protected string $content,
        protected string $mimeType,
        public int $width,
        public int $height,
    ) {}

    public function content(): string
    {
        return $this->content;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function base64(): string
    {
        return base64_encode($this->content);
    }

    public function dataUri(): string
    {
        return 'data:'.$this->mimeType.';base64,'.$this->base64();
    }

    public function save(string $path, ?string $disk = null): bool
    {
        return Storage::disk($disk)->put($path, $this->content);
    }
}
