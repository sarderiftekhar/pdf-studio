<?php

use PdfStudio\Laravel\DTOs\WatermarkOptions;
use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfWatermarker;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('implements WatermarkerContract', function () {
    $watermarker = new PdfWatermarker;

    expect($watermarker)->toBeInstanceOf(\PdfStudio\Laravel\Contracts\WatermarkerContract::class);
});

it('output method throws exception', function () {
    $watermarker = new PdfWatermarker;

    $watermarker->output();
})->throws(ManipulationException::class, 'Use apply()');

it('hexToRgb converts hex colors correctly', function () {
    $watermarker = new PdfWatermarker;
    $reflection = new ReflectionMethod($watermarker, 'hexToRgb');

    expect($reflection->invoke($watermarker, '#FF0000'))->toBe([255, 0, 0])
        ->and($reflection->invoke($watermarker, '#00FF00'))->toBe([0, 255, 0])
        ->and($reflection->invoke($watermarker, '#0000FF'))->toBe([0, 0, 255])
        ->and($reflection->invoke($watermarker, '999999'))->toBe([153, 153, 153]);
});

it('hexToRgb handles hash prefix', function () {
    $watermarker = new PdfWatermarker;
    $reflection = new ReflectionMethod($watermarker, 'hexToRgb');

    $withHash = $reflection->invoke($watermarker, '#AABBCC');
    $withoutHash = $reflection->invoke($watermarker, 'AABBCC');

    expect($withHash)->toBe($withoutHash);
});

it('accepts text watermark options', function () {
    $options = new WatermarkOptions(text: 'DRAFT', opacity: 0.5, fontSize: 72);

    expect($options->text)->toBe('DRAFT')
        ->and($options->opacity)->toBe(0.5)
        ->and($options->fontSize)->toBe(72)
        ->and($options->imagePath)->toBeNull();
});

it('accepts image watermark options', function () {
    $options = new WatermarkOptions(imagePath: '/path/to/logo.png', opacity: 0.2);

    expect($options->imagePath)->toBe('/path/to/logo.png')
        ->and($options->opacity)->toBe(0.2)
        ->and($options->text)->toBeNull();
});
