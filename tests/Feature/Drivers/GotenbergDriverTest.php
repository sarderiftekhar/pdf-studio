<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\GotenbergDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

it('implements RendererContract', function () {
    $driver = new GotenbergDriver(['url' => 'http://gotenberg.test']);

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('reports correct capabilities', function () {
    $driver = new GotenbergDriver(['url' => 'http://gotenberg.test']);
    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->headerFooter)->toBeTrue()
        ->and($caps->pageRanges)->toBeTrue()
        ->and($caps->preferCssPageSize)->toBeTrue()
        ->and($caps->scale)->toBeTrue()
        ->and($caps->waitUntil)->toBeTrue()
        ->and($caps->waitDelay)->toBeTrue()
        ->and($caps->waitForSelector)->toBeTrue()
        ->and($caps->waitForFunction)->toBeTrue()
        ->and($caps->taggedPdf)->toBeTrue()
        ->and($caps->metadata)->toBeTrue()
        ->and($caps->attachments)->toBeTrue()
        ->and($caps->pdfVariants)->toBeTrue();
});

it('builds a gotenberg multipart request with advanced options', function () {
    $attachmentPath = tempnam(sys_get_temp_dir(), 'pdfstudio_attachment_');
    file_put_contents($attachmentPath, 'attachment');

    $driver = new class(['url' => 'http://gotenberg.test']) extends GotenbergDriver
    {
        /** @var array{url: string, headers: array<string, string>, body: string, timeout: int}|null */
        public ?array $capturedRequest = null;

        protected function sendRequest(string $url, array $headers, string $body, int $timeout): array
        {
            $this->capturedRequest = compact('url', 'headers', 'body', 'timeout');

            return [
                'status' => 200,
                'body' => '%PDF-FAKE',
            ];
        }
    };

    $options = new RenderOptions(
        format: 'Letter',
        landscape: true,
        margins: ['top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15],
        printBackground: true,
        pageRanges: '1-3',
        preferCssPageSize: true,
        scale: 0.9,
        waitUntil: 'networkidle0',
        waitDelayMs: 750,
        waitForSelector: '#ready',
        waitForFunction: 'window.__PDF_READY === true',
        taggedPdf: true,
        headerHtml: '<div>Header</div>',
        footerHtml: '<div>Footer</div>',
        metadata: ['title' => 'Quarterly Report'],
        attachments: [[
            'name' => 'data.txt',
            'path' => $attachmentPath,
            'mime' => 'text/plain',
        ]],
        pdfVariant: 'pdf/ua-1',
    );

    $result = $driver->render('<html><body>Hello</body></html>', $options);

    expect($result)->toBe('%PDF-FAKE')
        ->and($driver->capturedRequest)->not->toBeNull()
        ->and($driver->capturedRequest['url'])->toBe('http://gotenberg.test/forms/chromium/convert/html')
        ->and($driver->capturedRequest['headers']['Accept'])->toBe('application/pdf')
        ->and($driver->capturedRequest['headers']['Content-Type'])->toContain('multipart/form-data')
        ->and($driver->capturedRequest['body'])->toContain('filename="index.html"')
        ->and($driver->capturedRequest['body'])->toContain('filename="header.html"')
        ->and($driver->capturedRequest['body'])->toContain('filename="footer.html"')
        ->and($driver->capturedRequest['body'])->toContain('filename="data.txt"')
        ->and($driver->capturedRequest['body'])->toContain('name="paperWidth"')
        ->and($driver->capturedRequest['body'])->toContain("name=\"paperWidth\"\r\n\r\n11")
        ->and($driver->capturedRequest['body'])->toContain('name="paperHeight"')
        ->and($driver->capturedRequest['body'])->toContain("name=\"paperHeight\"\r\n\r\n8.5")
        ->and($driver->capturedRequest['body'])->toContain('name="nativePageRanges"')
        ->and($driver->capturedRequest['body'])->toContain('1-3')
        ->and($driver->capturedRequest['body'])->toContain('name="waitDelay"')
        ->and($driver->capturedRequest['body'])->toContain('0.75s')
        ->and($driver->capturedRequest['body'])->toContain('name="skipNetworkIdleEvent"')
        ->and($driver->capturedRequest['body'])->toContain('false')
        ->and($driver->capturedRequest['body'])->toContain('name="waitForSelector"')
        ->and($driver->capturedRequest['body'])->toContain('#ready')
        ->and($driver->capturedRequest['body'])->toContain('name="waitForExpression"')
        ->and($driver->capturedRequest['body'])->toContain('window.__PDF_READY === true')
        ->and($driver->capturedRequest['body'])->toContain('name="generateTaggedPdf"')
        ->and($driver->capturedRequest['body'])->toContain('name="pdfua"')
        ->and($driver->capturedRequest['body'])->toContain('name="metadata"')
        ->and($driver->capturedRequest['body'])->toContain('"title":"Quarterly Report"');

    @unlink($attachmentPath);
});

it('throws when gotenberg url is missing', function () {
    new GotenbergDriver([]);
})->throws(DriverException::class, 'configured URL');

it('throws when an attachment path is invalid', function () {
    $driver = new GotenbergDriver(['url' => 'http://gotenberg.test']);

    $driver->render('<html></html>', new RenderOptions(
        attachments: [[
            'name' => 'missing.txt',
            'path' => '/tmp/does-not-exist.txt',
        ]],
    ));
})->throws(DriverException::class, 'valid file path');

it('throws on non-success gotenberg responses', function () {
    $driver = new class(['url' => 'http://gotenberg.test']) extends GotenbergDriver
    {
        protected function sendRequest(string $url, array $headers, string $body, int $timeout): array
        {
            return [
                'status' => 500,
                'body' => 'render failed',
            ];
        }
    };

    $driver->render('<html></html>', new RenderOptions);
})->throws(DriverException::class, 'status 500');
