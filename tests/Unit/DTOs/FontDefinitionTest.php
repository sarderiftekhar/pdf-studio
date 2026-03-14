<?php

use PdfStudio\Laravel\DTOs\FontDefinition;

it('stores font definition data', function () {
    $font = new FontDefinition(
        name: 'inter',
        family: 'Inter',
        sources: ['/tmp/Inter-Regular.ttf'],
        weight: '400',
        style: 'normal',
    );

    expect($font->name)->toBe('inter')
        ->and($font->family)->toBe('Inter')
        ->and($font->sources)->toBe(['/tmp/Inter-Regular.ttf'])
        ->and($font->weight)->toBe('400')
        ->and($font->style)->toBe('normal');
});
