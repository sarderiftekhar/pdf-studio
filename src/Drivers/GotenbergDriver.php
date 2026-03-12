<?php

namespace PdfStudio\Laravel\Drivers;

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

class GotenbergDriver implements RendererContract
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
        $url = rtrim((string) ($config['url'] ?? ''), '/');

        if ($url === '') {
            throw new DriverException('The Gotenberg driver requires a configured URL.');
        }

        $this->endpoint = $url.'/forms/chromium/convert/html';
        $this->timeout = (int) ($config['timeout'] ?? 60);
        $this->headers = array_filter(
            $config['headers'] ?? [],
            static fn ($value): bool => is_string($value) && $value !== ''
        );
    }

    public function render(string $html, RenderOptions $options): string
    {
        [$body, $contentType] = $this->buildMultipartBody($html, $options);

        $headers = array_merge($this->headers, [
            'Content-Type' => $contentType,
            'Accept' => 'application/pdf',
        ]);

        $response = $this->sendRequest($this->endpoint, $headers, $body, $this->timeout);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new DriverException(
                sprintf('Gotenberg rendering failed with status %d: %s', $response['status'], trim($response['body']))
            );
        }

        if (!str_starts_with($response['body'], '%PDF')) {
            throw new DriverException('Gotenberg returned unexpected output instead of PDF bytes.');
        }

        return $response['body'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function buildMultipartBody(string $html, RenderOptions $options): array
    {
        $boundary = '----pdfstudio_gotenberg_'.bin2hex(random_bytes(12));
        $parts = [];

        $parts[] = $this->filePart($boundary, 'files', 'index.html', $html, 'text/html');

        if ($options->headerHtml !== null) {
            $parts[] = $this->filePart($boundary, 'files', 'header.html', $options->headerHtml, 'text/html');
        }

        if ($options->footerHtml !== null) {
            $parts[] = $this->filePart($boundary, 'files', 'footer.html', $options->footerHtml, 'text/html');
        }

        foreach ($this->buildFormFields($options) as $name => $value) {
            if ($value === null) {
                continue;
            }

            $parts[] = $this->fieldPart($boundary, $name, $value);
        }

        foreach ($options->attachments as $attachment) {
            $path = $attachment['path'] ?? null;
            $name = $attachment['name'] ?? null;

            if (!is_string($path) || $path === '' || !is_file($path)) {
                throw new DriverException('Gotenberg attachments require a valid file path.');
            }

            $filename = is_string($name) && $name !== '' ? $name : basename($path);
            $content = file_get_contents($path);

            if ($content === false) {
                throw new DriverException("Unable to read attachment [{$path}] for Gotenberg render.");
            }

            $parts[] = $this->filePart(
                $boundary,
                'embeds',
                $filename,
                $content,
                is_string($attachment['mime'] ?? null) ? $attachment['mime'] : 'application/octet-stream',
            );
        }

        $parts[] = "--{$boundary}--\r\n";

        return [implode('', $parts), "multipart/form-data; boundary={$boundary}"];
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function buildFormFields(RenderOptions $options): array
    {
        [$paperWidth, $paperHeight] = $this->paperDimensions($options->format, $options->landscape);

        $fields = [
            'paperWidth' => $paperWidth,
            'paperHeight' => $paperHeight,
            'marginTop' => $this->toInches($options->margins['top']),
            'marginRight' => $this->toInches($options->margins['right']),
            'marginBottom' => $this->toInches($options->margins['bottom']),
            'marginLeft' => $this->toInches($options->margins['left']),
            'landscape' => $options->landscape ? 'true' : 'false',
            'printBackground' => $options->printBackground ? 'true' : 'false',
            'preferCssPageSize' => $options->preferCssPageSize ? 'true' : 'false',
            'scale' => $options->scale,
            'nativePageRanges' => $options->pageRanges,
            'waitDelay' => $options->waitDelayMs !== null ? $this->formatDelay($options->waitDelayMs) : null,
            'skipNetworkIdleEvent' => $this->shouldWaitForNetworkIdle($options) ? 'false' : null,
            'waitForSelector' => $options->waitForSelector,
            'waitForExpression' => $options->waitForFunction,
            'metadata' => $options->metadata !== [] ? json_encode($options->metadata, JSON_THROW_ON_ERROR) : null,
            'generateTaggedPdf' => $options->taggedPdf ? 'true' : null,
            'pdfa' => $this->resolvePdfA($options->pdfVariant),
            'pdfua' => $this->resolvePdfUa($options) ? 'true' : null,
        ];

        return $fields;
    }

    protected function resolvePdfA(?string $variant): ?string
    {
        if ($variant === null) {
            return null;
        }

        $normalized = strtoupper($variant);

        return str_starts_with($normalized, 'PDF/A-') ? $normalized : null;
    }

    protected function resolvePdfUa(RenderOptions $options): bool
    {
        if ($options->pdfVariant === null) {
            return false;
        }

        return str_starts_with(strtolower($options->pdfVariant), 'pdf/ua');
    }

    protected function shouldWaitForNetworkIdle(RenderOptions $options): bool
    {
        return in_array($options->waitUntil, ['networkidle0', 'networkidle2'], true);
    }

    protected function formatDelay(int $milliseconds): string
    {
        $seconds = $milliseconds / 1000;

        return rtrim(rtrim(number_format($seconds, 3, '.', ''), '0'), '.').'s';
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function paperDimensions(string $format, bool $landscape): array
    {
        $dimensions = match (strtoupper($format)) {
            'LETTER' => [8.5, 11.0],
            'LEGAL' => [8.5, 14.0],
            'TABLOID' => [11.0, 17.0],
            'LEDGER' => [17.0, 11.0],
            'A0' => [33.1, 46.8],
            'A1' => [23.4, 33.1],
            'A2' => [16.54, 23.4],
            'A3' => [11.7, 16.54],
            'A4' => [8.27, 11.7],
            'A5' => [5.83, 8.27],
            'A6' => [4.13, 5.83],
            default => [8.27, 11.7],
        };

        return $landscape ? [$dimensions[1], $dimensions[0]] : $dimensions;
    }

    protected function toInches(int $millimeters): string
    {
        return number_format($millimeters / 25.4, 2, '.', '');
    }

    protected function fieldPart(string $boundary, string $name, string|int|float $value): string
    {
        return "--{$boundary}\r\n"
            ."Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n"
            ."{$value}\r\n";
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

            throw new DriverException('Unable to connect to Gotenberg: '.($error['message'] ?? 'unknown error'));
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

    public function supports(): DriverCapabilities
    {
        return new DriverCapabilities(
            landscape: true,
            customMargins: true,
            headerFooter: true,
            printBackground: true,
            supportedFormats: ['Letter', 'Legal', 'Tabloid', 'Ledger', 'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6'],
            autoHeight: false,
            pageRanges: true,
            preferCssPageSize: true,
            scale: true,
            waitForFonts: false,
            waitUntil: true,
            waitDelay: true,
            waitForSelector: true,
            waitForFunction: true,
            taggedPdf: true,
            outline: false,
            metadata: true,
            attachments: true,
            pdfVariants: true,
        );
    }
}
