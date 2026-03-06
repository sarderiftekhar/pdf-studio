<?php

namespace PdfStudio\Laravel\Output;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfResult
{
    public string $mimeType = 'application/pdf';

    public int $bytes;

    public function __construct(
        protected string $content,
        public string $driver,
        public float $renderTimeMs,
    ) {
        $this->bytes = strlen($content);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function base64(): string
    {
        return base64_encode($this->content);
    }

    public function download(string $filename): Response
    {
        return new Response($this->content, 200, [
            'Content-Type' => $this->mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => $this->bytes,
        ]);
    }

    public function stream(string $filename): StreamedResponse
    {
        $content = $this->content;

        return new StreamedResponse(function () use ($content) {
            echo $content;
        }, 200, [
            'Content-Type' => $this->mimeType,
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function save(string $path, ?string $disk = null): StorageResult
    {
        $disk = $disk ?? config('pdf-studio.output.default_disk') ?? config('filesystems.default');
        $storage = Storage::disk($disk);

        $storage->put($path, $this->content);

        return new StorageResult(
            path: $path,
            disk: $disk,
            bytes: $this->bytes,
        );
    }
}
