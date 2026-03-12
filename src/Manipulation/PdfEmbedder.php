<?php

namespace PdfStudio\Laravel\Manipulation;

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Output\PdfResult;

class PdfEmbedder
{
    protected string $endpoint;

    protected int $timeout;

    /** @var array<string, string> */
    protected array $headers;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
        $url = rtrim((string) ($config['url'] ?? config('pdf-studio.drivers.gotenberg.url', '')), '/');

        if ($url === '') {
            throw new ManipulationException('PDF embedding requires a configured Gotenberg URL.');
        }

        $this->endpoint = $url.'/forms/pdfengines/embed';
        $this->timeout = (int) ($config['timeout'] ?? config('pdf-studio.drivers.gotenberg.timeout', 60));
        $this->headers = array_filter(
            $config['headers'] ?? config('pdf-studio.drivers.gotenberg.headers', []),
            static fn ($value): bool => is_string($value) && $value !== ''
        );
    }

    /**
     * @param  array<int, array{path: string, name?: string|null, mime?: string|null}>  $files
     */
    public function embed(string $pdfContent, array $files): PdfResult
    {
        if ($files === []) {
            throw new ManipulationException('At least one file is required for PDF embedding.');
        }

        [$body, $contentType] = $this->buildMultipartBody($pdfContent, $files);
        $headers = array_merge($this->headers, [
            'Content-Type' => $contentType,
            'Accept' => 'application/pdf',
        ]);

        $response = $this->sendRequest($this->endpoint, $headers, $body, $this->timeout);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new ManipulationException(
                sprintf('Gotenberg PDF embed failed with status %d: %s', $response['status'], trim($response['body']))
            );
        }

        if (!str_starts_with($response['body'], '%PDF')) {
            throw new ManipulationException('Gotenberg returned unexpected output instead of embedded PDF bytes.');
        }

        return new PdfResult(
            content: $response['body'],
            driver: 'gotenberg-embedder',
            renderTimeMs: 0,
        );
    }

    /**
     * @param  array<int, array{path: string, name?: string|null, mime?: string|null}>  $files
     * @return array{0: string, 1: string}
     */
    protected function buildMultipartBody(string $pdfContent, array $files): array
    {
        $boundary = '----pdfstudio_embed_'.bin2hex(random_bytes(12));
        $parts = [];

        $parts[] = $this->filePart($boundary, 'files', 'document.pdf', $pdfContent, 'application/pdf');

        foreach ($files as $file) {
            $path = $file['path'] ?? null;

            if (!is_string($path) || $path === '' || !is_file($path)) {
                throw new ManipulationException('Embedded files require a valid file path.');
            }

            $content = file_get_contents($path);

            if ($content === false) {
                throw new ManipulationException("Unable to read embedded file [{$path}].");
            }

            $filename = isset($file['name']) && is_string($file['name']) && $file['name'] !== ''
                ? $file['name']
                : basename($path);

            $mime = isset($file['mime']) && is_string($file['mime']) && $file['mime'] !== ''
                ? $file['mime']
                : 'application/octet-stream';

            $parts[] = $this->filePart($boundary, 'embeds', $filename, $content, $mime);
        }

        $parts[] = "--{$boundary}--\r\n";

        return [implode('', $parts), "multipart/form-data; boundary={$boundary}"];
    }

    protected function filePart(
        string $boundary,
        string $name,
        string $filename,
        string $content,
        string $contentType,
    ): string {
        return "--{$boundary}\r\n"
            ."Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n"
            ."Content-Type: {$contentType}\r\n\r\n"
            ."{$content}\r\n";
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{status: int, body: string}
     */
    protected function sendRequest(string $url, array $headers, string $body, int $timeout): array
    {
        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            $error = error_get_last();

            throw new ManipulationException('Unable to connect to Gotenberg embed route: '.($error['message'] ?? 'unknown error'));
        }

        $status = 0;

        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches) === 1) {
                $status = (int) $matches[1];
                break;
            }
        }

        return [
            'status' => $status,
            'body' => $responseBody,
        ];
    }
}
