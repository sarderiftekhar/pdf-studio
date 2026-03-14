<?php

use PdfStudio\Laravel\Barcode\QrCodeGenerator;

it('generates SVG QR code', function () {
    $generator = new QrCodeGenerator;
    $svg = $generator->generate('https://example.com');

    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

it('generates QR code with custom size', function () {
    $generator = new QrCodeGenerator;
    $svg = $generator->generate('https://example.com', ['size' => 200]);

    expect($svg)->toContain('<svg');
});

it('supports error correction levels', function () {
    $generator = new QrCodeGenerator;
    $svg = $generator->generate('test', ['error_correction' => 'H']);

    expect($svg)->toContain('<svg');
});
