<?php

use PdfStudio\Laravel\DTOs\DriverCapabilities;

it('has sensible defaults', function () {
    $caps = new DriverCapabilities;

    expect($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeFalse()
        ->and($caps->printBackground)->toBeTrue()
        ->and($caps->supportedFormats)->toBe(['A4', 'Letter', 'Legal'])
        ->and($caps->pageRanges)->toBeFalse()
        ->and($caps->preferCssPageSize)->toBeFalse()
        ->and($caps->scale)->toBeFalse()
        ->and($caps->waitForFonts)->toBeFalse()
        ->and($caps->waitUntil)->toBeFalse()
        ->and($caps->waitDelay)->toBeFalse()
        ->and($caps->waitForSelector)->toBeFalse()
        ->and($caps->waitForFunction)->toBeFalse()
        ->and($caps->taggedPdf)->toBeFalse()
        ->and($caps->outline)->toBeFalse()
        ->and($caps->metadata)->toBeFalse()
        ->and($caps->attachments)->toBeFalse()
        ->and($caps->pdfVariants)->toBeFalse();
});
