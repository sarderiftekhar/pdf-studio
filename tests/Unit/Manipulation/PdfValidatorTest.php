<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfValidator;

it('returns true for pdf-like content', function () {
    $validator = new PdfValidator;

    expect($validator->isPdf("%PDF-1.7\n1 0 obj\n<<>>\nendobj\n%%EOF"))->toBeTrue();
});

it('returns true for pdf-like content with leading whitespace', function () {
    $validator = new PdfValidator;

    expect($validator->isPdf("  \n\t%PDF-1.4\nbody\n%%EOF"))->toBeTrue();
});

it('returns false for non-pdf content', function () {
    $validator = new PdfValidator;

    expect($validator->isPdf('<html>not a pdf</html>'))->toBeFalse();
});

it('returns false for truncated pdf-like content', function () {
    $validator = new PdfValidator;

    expect($validator->isPdf("%PDF-1.7\nbody without eof"))->toBeFalse();
});

it('does not throw when asserting valid pdf-like content', function () {
    $validator = new PdfValidator;

    $validator->assertPdf("%PDF-1.7\n1 0 obj\n<<>>\nendobj\n%%EOF");

    expect(true)->toBeTrue();
});

it('throws when asserting invalid pdf-like content', function () {
    $validator = new PdfValidator;

    $validator->assertPdf('<html>not a pdf</html>', 'upload');
})->throws(ManipulationException::class, 'provided upload is not a valid PDF payload');
