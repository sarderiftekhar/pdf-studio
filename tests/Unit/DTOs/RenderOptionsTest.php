<?php

use PdfStudio\Laravel\DTOs\RenderOptions;

it('has sensible defaults', function () {
    $options = new RenderOptions;

    expect($options->format)->toBe('A4')
        ->and($options->landscape)->toBeFalse()
        ->and($options->margins)->toBe(['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10])
        ->and($options->printBackground)->toBeTrue()
        ->and($options->pageRanges)->toBeNull()
        ->and($options->preferCssPageSize)->toBeFalse()
        ->and($options->scale)->toBe(1.0)
        ->and($options->waitForFonts)->toBeFalse()
        ->and($options->waitUntil)->toBeNull()
        ->and($options->waitDelayMs)->toBeNull()
        ->and($options->waitForSelector)->toBeNull()
        ->and($options->waitForFunction)->toBeNull()
        ->and($options->taggedPdf)->toBeFalse()
        ->and($options->outline)->toBeFalse()
        ->and($options->headerHtml)->toBeNull()
        ->and($options->footerHtml)->toBeNull()
        ->and($options->metadata)->toBe([])
        ->and($options->attachments)->toBe([])
        ->and($options->pdfVariant)->toBeNull();
});

it('accepts custom values', function () {
    $options = new RenderOptions(
        format: 'Letter',
        landscape: true,
        margins: ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
        printBackground: false,
        pageRanges: '1-3',
        preferCssPageSize: true,
        scale: 0.9,
        waitForFonts: true,
        waitUntil: 'networkidle0',
        waitDelayMs: 750,
        waitForSelector: '#ready',
        waitForSelectorOptions: ['visible' => true],
        waitForFunction: 'window.__PDF_READY === true',
        waitForFunctionTimeout: 5000,
        taggedPdf: true,
        outline: true,
        headerHtml: '<p>Header</p>',
        footerHtml: '<p>Footer</p>',
        metadata: ['title' => 'Quarterly Report'],
        attachments: [['name' => 'data.csv', 'path' => '/tmp/data.csv']],
        pdfVariant: 'pdf/a-2b',
    );

    expect($options->format)->toBe('Letter')
        ->and($options->landscape)->toBeTrue()
        ->and($options->margins['top'])->toBe(20)
        ->and($options->printBackground)->toBeFalse()
        ->and($options->pageRanges)->toBe('1-3')
        ->and($options->preferCssPageSize)->toBeTrue()
        ->and($options->scale)->toBe(0.9)
        ->and($options->waitForFonts)->toBeTrue()
        ->and($options->waitUntil)->toBe('networkidle0')
        ->and($options->waitDelayMs)->toBe(750)
        ->and($options->waitForSelector)->toBe('#ready')
        ->and($options->waitForSelectorOptions)->toBe(['visible' => true])
        ->and($options->waitForFunction)->toBe('window.__PDF_READY === true')
        ->and($options->waitForFunctionTimeout)->toBe(5000)
        ->and($options->taggedPdf)->toBeTrue()
        ->and($options->outline)->toBeTrue()
        ->and($options->headerHtml)->toBe('<p>Header</p>')
        ->and($options->footerHtml)->toBe('<p>Footer</p>')
        ->and($options->metadata)->toBe(['title' => 'Quarterly Report'])
        ->and($options->attachments)->toHaveCount(1)
        ->and($options->pdfVariant)->toBe('pdf/a-2b');
});
