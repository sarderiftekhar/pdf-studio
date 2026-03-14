<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfEmbedder;
use PdfStudio\Laravel\Output\PdfResult;

it('throws when no files are provided for embedding', function () {
    $embedder = new PdfEmbedder([
        'url' => 'http://gotenberg.test',
        'timeout' => 60,
        'headers' => [],
    ]);

    $embedder->embed('%PDF-fake', []);
})->throws(ManipulationException::class, 'At least one file');

it('throws when an embedded file path is invalid', function () {
    $embedder = new PdfEmbedder([
        'url' => 'http://gotenberg.test',
        'timeout' => 60,
        'headers' => [],
    ]);

    $embedder->embed('%PDF-fake', [[
        'path' => '/tmp/does-not-exist.txt',
    ]]);
})->throws(ManipulationException::class, 'valid file path');

it('builds a gotenberg embed request and returns a PdfResult', function () {
    $attachmentPath = tempnam(sys_get_temp_dir(), 'pdfstudio_embed_');
    file_put_contents($attachmentPath, 'embedded-data');

    $embedder = new class([
        'url' => 'http://gotenberg.test',
        'timeout' => 60,
        'headers' => [],
    ]) extends PdfEmbedder
    {
        /** @var array{url: string, headers: array<string, string>, body: string, timeout: int}|null */
        public ?array $capturedRequest = null;

        protected function sendRequest(string $url, array $headers, string $body, int $timeout): array
        {
            $this->capturedRequest = compact('url', 'headers', 'body', 'timeout');

            return [
                'status' => 200,
                'body' => '%PDF-EMBEDDED',
            ];
        }
    };

    $result = $embedder->embed('%PDF-fake', [[
        'path' => $attachmentPath,
        'name' => 'evidence.txt',
        'mime' => 'text/plain',
    ]]);

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('%PDF-EMBEDDED')
        ->and($result->driver)->toBe('gotenberg-embedder')
        ->and($embedder->capturedRequest)->not->toBeNull()
        ->and($embedder->capturedRequest['url'])->toBe('http://gotenberg.test/forms/pdfengines/embed')
        ->and($embedder->capturedRequest['headers']['Accept'])->toBe('application/pdf')
        ->and($embedder->capturedRequest['headers']['Content-Type'])->toContain('multipart/form-data')
        ->and($embedder->capturedRequest['body'])->toContain('filename="document.pdf"')
        ->and($embedder->capturedRequest['body'])->toContain('filename="evidence.txt"')
        ->and($embedder->capturedRequest['body'])->toContain('name="files"')
        ->and($embedder->capturedRequest['body'])->toContain('name="embeds"');

    @unlink($attachmentPath);
});

it('throws on non-success gotenberg embed responses', function () {
    $attachmentPath = tempnam(sys_get_temp_dir(), 'pdfstudio_embed_');
    file_put_contents($attachmentPath, 'embedded-data');

    $embedder = new class([
        'url' => 'http://gotenberg.test',
        'timeout' => 60,
        'headers' => [],
    ]) extends PdfEmbedder
    {
        protected function sendRequest(string $url, array $headers, string $body, int $timeout): array
        {
            return [
                'status' => 500,
                'body' => 'embed failed',
            ];
        }
    };

    try {
        $embedder->embed('%PDF-fake', [[
            'path' => $attachmentPath,
            'name' => 'evidence.txt',
        ]]);
    } finally {
        @unlink($attachmentPath);
    }
})->throws(ManipulationException::class, 'status 500');

it('throws when gotenberg returns non-pdf output for embed requests', function () {
    $attachmentPath = tempnam(sys_get_temp_dir(), 'pdfstudio_embed_');
    file_put_contents($attachmentPath, 'embedded-data');

    $embedder = new class([
        'url' => 'http://gotenberg.test',
        'timeout' => 60,
        'headers' => [],
    ]) extends PdfEmbedder
    {
        protected function sendRequest(string $url, array $headers, string $body, int $timeout): array
        {
            return [
                'status' => 200,
                'body' => 'not-a-pdf',
            ];
        }
    };

    try {
        $embedder->embed('%PDF-fake', [[
            'path' => $attachmentPath,
            'name' => 'evidence.txt',
        ]]);
    } finally {
        @unlink($attachmentPath);
    }
})->throws(ManipulationException::class, 'unexpected output');
