<?php

namespace PdfStudio\Laravel\Drivers;

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

class CloudflareDriver implements RendererContract
{
    protected string $accountId;

    protected string $apiToken;

    protected string $baseUrl;

    protected int $timeout;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {
        $this->accountId = (string) ($config['account_id'] ?? '');
        $this->apiToken = (string) ($config['api_token'] ?? '');
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.cloudflare.com/client/v4'), '/');
        $this->timeout = (int) ($config['timeout'] ?? 30);

        if ($this->accountId === '' || $this->apiToken === '') {
            throw new DriverException('The Cloudflare driver requires [account_id] and [api_token] configuration.');
        }
    }

    public function render(string $html, RenderOptions $options): string
    {
        $payload = $this->buildPayload($html, $options);
        $url = "{$this->baseUrl}/accounts/{$this->accountId}/browser-rendering/pdf";
        $response = $this->sendRequest($url, $payload, $this->timeout);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new DriverException(
                sprintf('Cloudflare rendering failed with status %d: %s', $response['status'], trim($response['body']))
            );
        }

        if (!str_starts_with($response['body'], '%PDF')) {
            throw new DriverException('Cloudflare returned unexpected output instead of PDF bytes.');
        }

        return $response['body'];
    }

    /**
     * Build the Browser Rendering request body. PDF options live under `pdfOptions`
     * and readiness options at the top level, mirroring the REST API schema.
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(string $html, RenderOptions $options): array
    {
        $pdfOptions = [
            'printBackground' => $options->printBackground,
            'landscape' => $options->landscape,
            'scale' => $options->scale,
            'preferCSSPageSize' => $options->preferCssPageSize,
        ];

        if ($options->format !== '') {
            $pdfOptions['format'] = strtolower($options->format);
        }

        if ($options->pageRanges !== null) {
            $pdfOptions['pageRanges'] = $options->pageRanges;
        }

        if ($options->headerHtml !== null || $options->footerHtml !== null) {
            $pdfOptions['displayHeaderFooter'] = true;
            $pdfOptions['headerTemplate'] = $options->headerHtml ?? '<span></span>';
            $pdfOptions['footerTemplate'] = $options->footerHtml ?? '<span></span>';
        }

        if ($options->margins !== []) {
            // Bare numbers are treated as pixels by the API, so send explicit units.
            $pdfOptions['margin'] = array_map(
                static fn (int $millimeters): string => "{$millimeters}mm",
                array_intersect_key($options->margins, array_flip(['top', 'right', 'bottom', 'left'])),
            );
        }

        $payload = [
            'html' => $html,
            'pdfOptions' => $pdfOptions,
            'gotoOptions' => array_filter([
                'waitUntil' => $options->waitUntil,
                'timeout' => min($this->timeout * 1000, 60000),
            ], static fn ($value): bool => $value !== null),
        ];

        if ($options->waitDelayMs !== null) {
            $payload['waitForTimeout'] = $options->waitDelayMs;
        }

        if ($options->waitForSelector !== null) {
            $payload['waitForSelector'] = ['selector' => $options->waitForSelector];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: int, body: string}
     */
    protected function sendRequest(string $url, array $payload, int $timeout): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    "Authorization: Bearer {$this->apiToken}",
                    'Content-Type: application/json',
                    'Accept: application/pdf',
                ]),
                'content' => $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $http_response_header = [];
        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            $error = error_get_last();

            throw new DriverException('Unable to connect to Cloudflare Browser Rendering: '.($error['message'] ?? 'unknown error'));
        }

        $status = 0;

        foreach ($http_response_header as $line) {
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
            waitForFunction: false,
            taggedPdf: false,
            outline: false,
            metadata: false,
            attachments: false,
            pdfVariants: false,
        );
    }
}
