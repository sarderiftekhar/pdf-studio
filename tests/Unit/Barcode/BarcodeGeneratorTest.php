<?php

use PdfStudio\Laravel\Barcode\BarcodeGenerator;

it('generates SVG barcode for CODE128', function () {
    $generator = new BarcodeGenerator;
    $svg = $generator->generate('CODE128', '12345678');

    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

it('generates SVG barcode with custom dimensions', function () {
    $generator = new BarcodeGenerator;
    $svg = $generator->generate('CODE128', '12345678', ['width' => 3, 'height' => 80]);

    expect($svg)->toContain('<svg');
});

it('generates EAN13 barcode', function () {
    $generator = new BarcodeGenerator;
    $svg = $generator->generate('EAN13', '5901234123457');

    expect($svg)->toContain('<svg');
});
