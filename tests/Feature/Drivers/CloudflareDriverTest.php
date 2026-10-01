<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\CloudflareDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

it('implements RendererContract', function () {
    $driver = new CloudflareDriver([
        'account_id' => 'acc_123',
        'api_token' => 'token_123',
    ]);

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('reports correct capabilities', function () {
    $driver = new CloudflareDriver([
        'account_id' => 'acc_123',
        'api_token' => 'token_123',
    ]);
    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->headerFooter)->toBeTrue()
        ->and($caps->pageRanges)->toBeTrue()
        ->and($caps->preferCssPageSize)->toBeTrue()
        ->and($caps->scale)->toBeTrue()
        ->and($caps->waitUntil)->toBeTrue()
        ->and($caps->waitDelay)->toBeTrue()
        ->and($caps->waitForSelector)->toBeTrue();
});

it('builds a cloudflare pdf payload with readiness and print options', function () {
    $driver = new class(['account_id' => 'acc_123', 'api_token' => 'token_123']) extends CloudflareDriver
    {
        /** @var array{url: string, payload: array<string, mixed>, timeout: int}|null */
        public ?array $capturedRequest = null;

        protected function sendRequest(string $url, array $payload, int $timeout): array
        {
            $this->capturedRequest = compact('url', 'payload', 'timeout');

            return [
                'status' => 200,
                'body' => '%PDF-FAKE',
            ];
        }
    };

    $options = new RenderOptions(
        format: 'Letter',
        landscape: true,
        printBackground: true,
        pageRanges: '1-3',
        preferCssPageSize: true,
        scale: 0.9,
        waitUntil: 'networkidle0',
        waitDelayMs: 750,
        waitForSelector: '#ready',
        headerHtml: '<div>Header</div>',
        footerHtml: '<div>Footer</div>',
        margins: ['top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15],
    );

    $result = $driver->render('<html><body>Hello</body></html>', $options);

    expect($result)->toBe('%PDF-FAKE')
        ->and($driver->capturedRequest)->not->toBeNull()
        ->and($driver->capturedRequest['url'])->toContain('/accounts/acc_123/browser-rendering/pdf')
        ->and($driver->capturedRequest['payload'])->toMatchArray([
            'html' => '<html><body>Hello</body></html>',
            'waitForTimeout' => 750,
            'waitForSelector' => ['selector' => '#ready'],
        ])
        ->and($driver->capturedRequest['payload'])->not->toHaveKeys(['format', 'margin', 'landscape', 'headerTemplate'])
        ->and($driver->capturedRequest['payload']['pdfOptions'])->toMatchArray([
            'printBackground' => true,
            'landscape' => true,
            'scale' => 0.9,
            'preferCSSPageSize' => true,
            'format' => 'letter',
            'pageRanges' => '1-3',
            'displayHeaderFooter' => true,
            'headerTemplate' => '<div>Header</div>',
            'footerTemplate' => '<div>Footer</div>',
            'margin' => ['top' => '20mm', 'right' => '15mm', 'bottom' => '20mm', 'left' => '15mm'],
        ])
        ->and($driver->capturedRequest['payload']['gotoOptions'])->toMatchArray([
            'waitUntil' => 'networkidle0',
            'timeout' => 30000,
        ]);
});

it('sends A4 and default margins in the API shape', function () {
    $driver = new class(['account_id' => 'acc_123', 'api_token' => 'token_123']) extends CloudflareDriver
    {
        /** @var array<string, mixed> */
        public array $payload = [];

        protected function sendRequest(string $url, array $payload, int $timeout): array
        {
            $this->payload = $payload;

            return ['status' => 200, 'body' => '%PDF-FAKE'];
        }
    };

    $driver->render('<html></html>', new RenderOptions);

    expect($driver->payload['pdfOptions']['format'])->toBe('a4')
        ->and($driver->payload['pdfOptions']['margin'])->toBe(['top' => '10mm', 'right' => '10mm', 'bottom' => '10mm', 'left' => '10mm'])
        ->and($driver->payload['pdfOptions'])->not->toHaveKey('displayHeaderFooter')
        ->and($driver->payload)->not->toHaveKeys(['waitForSelector', 'waitForTimeout']);
});

it('throws when cloudflare credentials are missing', function () {
    new CloudflareDriver([]);
})->throws(DriverException::class, 'account_id');

it('throws on non-success cloudflare responses', function () {
    $driver = new class(['account_id' => 'acc_123', 'api_token' => 'token_123']) extends CloudflareDriver
    {
        protected function sendRequest(string $url, array $payload, int $timeout): array
        {
            return [
                'status' => 403,
                'body' => 'forbidden',
            ];
        }
    };

    $driver->render('<html></html>', new RenderOptions);
})->throws(DriverException::class, 'status 403');
