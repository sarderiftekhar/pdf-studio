<?php

use PdfStudio\Laravel\DTOs\DriverCapabilities;

it('has sensible defaults', function () {
    $caps = new DriverCapabilities;

    expect($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeFalse()
        ->and($caps->printBackground)->toBeTrue()
        ->and($caps->supportedFormats)->toBe(['A4', 'Letter', 'Legal']);
});
