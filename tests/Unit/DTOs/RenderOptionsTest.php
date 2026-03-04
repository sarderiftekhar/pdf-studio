<?php

use PdfStudio\Laravel\DTOs\RenderOptions;

it('has sensible defaults', function () {
    $options = new RenderOptions;

    expect($options->format)->toBe('A4')
        ->and($options->landscape)->toBeFalse()
        ->and($options->margins)->toBe(['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10])
        ->and($options->printBackground)->toBeTrue()
        ->and($options->headerHtml)->toBeNull()
        ->and($options->footerHtml)->toBeNull();
});

it('accepts custom values', function () {
    $options = new RenderOptions(
        format: 'Letter',
        landscape: true,
        margins: ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
        printBackground: false,
        headerHtml: '<p>Header</p>',
        footerHtml: '<p>Footer</p>',
    );

    expect($options->format)->toBe('Letter')
        ->and($options->landscape)->toBeTrue()
        ->and($options->margins['top'])->toBe(20)
        ->and($options->printBackground)->toBeFalse()
        ->and($options->headerHtml)->toBe('<p>Header</p>')
        ->and($options->footerHtml)->toBe('<p>Footer</p>');
});
