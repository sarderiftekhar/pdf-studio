<?php

use Illuminate\Support\Facades\Blade;

it('compiles @barcode directive', function () {
    $compiled = Blade::compileString("@barcode('CODE128', '12345')");

    expect($compiled)->toContain('BarcodeGenerator');
    expect($compiled)->toContain('generate');
});

it('compiles @qrcode directive', function () {
    $compiled = Blade::compileString("@qrcode('https://example.com')");

    expect($compiled)->toContain('QrCodeGenerator');
    expect($compiled)->toContain('generate');
});
