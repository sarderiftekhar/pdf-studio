<?php

use PdfStudio\Laravel\DTOs\WatermarkOptions;

it('has sensible defaults', function () {
    $options = new WatermarkOptions;

    expect($options->text)->toBeNull()
        ->and($options->imagePath)->toBeNull()
        ->and($options->opacity)->toBe(0.3)
        ->and($options->rotation)->toBe(-45)
        ->and($options->position)->toBe('center')
        ->and($options->fontSize)->toBe(48)
        ->and($options->color)->toBe('#999999');
});

it('accepts all constructor parameters', function () {
    $options = new WatermarkOptions(
        text: 'CONFIDENTIAL',
        imagePath: '/path/to/image.png',
        opacity: 0.5,
        rotation: -30,
        position: 'top-left',
        fontSize: 72,
        color: '#FF0000',
    );

    expect($options->text)->toBe('CONFIDENTIAL')
        ->and($options->imagePath)->toBe('/path/to/image.png')
        ->and($options->opacity)->toBe(0.5)
        ->and($options->rotation)->toBe(-30)
        ->and($options->position)->toBe('top-left')
        ->and($options->fontSize)->toBe(72)
        ->and($options->color)->toBe('#FF0000');
});

it('allows text-only watermark', function () {
    $options = new WatermarkOptions(text: 'DRAFT');

    expect($options->text)->toBe('DRAFT')
        ->and($options->imagePath)->toBeNull();
});

it('allows image-only watermark', function () {
    $options = new WatermarkOptions(imagePath: '/logo.png');

    expect($options->imagePath)->toBe('/logo.png')
        ->and($options->text)->toBeNull();
});

it('supports all position values', function () {
    $positions = ['center', 'top-left', 'top-right', 'bottom-left', 'bottom-right'];

    foreach ($positions as $position) {
        $options = new WatermarkOptions(position: $position);
        expect($options->position)->toBe($position);
    }
});
