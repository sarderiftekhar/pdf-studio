<?php

use PdfStudio\Laravel\DTOs\FontDefinition;
use PdfStudio\Laravel\Fonts\FontRegistry;

it('loads configured fonts into the registry', function () {
    config([
        'pdf-studio.fonts' => [
            'inter' => [
                'family' => 'Inter',
                'sources' => ['/tmp/Inter-Regular.ttf'],
                'weight' => '400',
                'style' => 'normal',
            ],
        ],
    ]);

    $this->app->forgetInstance(FontRegistry::class);
    $registry = app(FontRegistry::class);

    expect($registry->has('inter'))->toBeTrue();

    $font = $registry->get('inter');

    expect($font)->toBeInstanceOf(FontDefinition::class)
        ->and($font?->family)->toBe('Inter')
        ->and($font?->sources)->toBe(['/tmp/Inter-Regular.ttf']);
});

it('allows fonts to be registered programmatically', function () {
    $registry = app(FontRegistry::class);

    $registry->register(new FontDefinition(
        name: 'noto-sans-arabic',
        family: 'Noto Sans Arabic',
        sources: ['/tmp/NotoSansArabic-Regular.ttf'],
    ));

    expect($registry->has('noto-sans-arabic'))->toBeTrue()
        ->and($registry->get('noto-sans-arabic')?->family)->toBe('Noto Sans Arabic');
});
