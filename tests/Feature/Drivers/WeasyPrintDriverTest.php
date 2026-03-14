<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\WeasyPrintDriver;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\DriverException;

it('implements RendererContract', function () {
    $driver = new WeasyPrintDriver(['binary' => 'weasyprint']);

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('reports correct capabilities', function () {
    $driver = new WeasyPrintDriver(['binary' => 'weasyprint']);
    $caps = $driver->supports();

    expect($caps)->toBeInstanceOf(DriverCapabilities::class)
        ->and($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeFalse()
        ->and($caps->preferCssPageSize)->toBeTrue()
        ->and($caps->taggedPdf)->toBeTrue()
        ->and($caps->metadata)->toBeTrue()
        ->and($caps->attachments)->toBeTrue()
        ->and($caps->pdfVariants)->toBeTrue();
});

it('builds a weasyprint command and injects print styles and metadata', function () {
    $attachmentPath = tempnam(sys_get_temp_dir(), 'pdfstudio_weasy_attach_');
    file_put_contents($attachmentPath, 'attachment');

    $driver = new class(['binary' => 'weasyprint']) extends WeasyPrintDriver
    {
        /** @var array<int, string>|null */
        public ?array $capturedCommand = null;

        public ?string $capturedHtml = null;

        protected function executeCommand(array $command, int $timeout): void
        {
            $this->capturedCommand = $command;

            $inputHtml = file_get_contents($command[1]);
            $this->capturedHtml = $inputHtml === false ? null : $inputHtml;

            file_put_contents($command[2], '%PDF-FAKE');
        }
    };

    $options = new RenderOptions(
        format: 'A4',
        landscape: true,
        margins: ['top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15],
        taggedPdf: true,
        metadata: ['title' => 'Annual Report', 'author' => 'PDF Studio'],
        attachments: [[
            'name' => 'data.txt',
            'path' => $attachmentPath,
        ]],
        pdfVariant: 'pdf/a-3u',
    );

    $result = $driver->render('<html><head></head><body><h1>Hello</h1></body></html>', $options);

    expect($result)->toBe('%PDF-FAKE')
        ->and($driver->capturedCommand)->not->toBeNull()
        ->and($driver->capturedCommand[0])->toBe('weasyprint')
        ->and($driver->capturedCommand)->toContain('--attachment')
        ->and($driver->capturedCommand)->toContain($attachmentPath)
        ->and($driver->capturedCommand)->toContain('--pdf-variant')
        ->and($driver->capturedCommand)->toContain('pdf/a-3u')
        ->and($driver->capturedCommand)->toContain('--custom-metadata')
        ->and($driver->capturedCommand)->toContain('--pdf-tags')
        ->and($driver->capturedHtml)->toContain('@page { size: A4 landscape; margin: 20mm 15mm 20mm 15mm; }')
        ->and($driver->capturedHtml)->toContain('<meta name="title" content="Annual Report">')
        ->and($driver->capturedHtml)->toContain('<meta name="author" content="PDF Studio">');

    @unlink($attachmentPath);
});

it('throws when an attachment path is invalid', function () {
    $driver = new WeasyPrintDriver(['binary' => 'weasyprint']);

    $driver->render('<html></html>', new RenderOptions(
        attachments: [[
            'path' => '/tmp/missing-weasyprint-attachment.txt',
        ]],
    ));
})->throws(DriverException::class, 'valid file path');

it('throws when weasyprint returns non-pdf output', function () {
    $driver = new class(['binary' => 'weasyprint']) extends WeasyPrintDriver
    {
        protected function executeCommand(array $command, int $timeout): void
        {
            file_put_contents($command[2], 'not-a-pdf');
        }
    };

    $driver->render('<html></html>', new RenderOptions);
})->throws(DriverException::class, 'unexpected output');

it('throws when the process fails', function () {
    $driver = new class(['binary' => 'weasyprint']) extends WeasyPrintDriver
    {
        protected function executeCommand(array $command, int $timeout): void
        {
            throw new DriverException('WeasyPrint rendering failed: boom');
        }
    };

    $driver->render('<html></html>', new RenderOptions);
})->throws(DriverException::class, 'boom');
