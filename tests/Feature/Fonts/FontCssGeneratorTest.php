<?php

use PdfStudio\Laravel\Fonts\FontCssGenerator;
use PdfStudio\Laravel\Fonts\FontRegistry;

it('generates @font-face css with embedded font data', function () {
    $fontFile = tempnam(sys_get_temp_dir(), 'pdfstudio_font_css_').'.ttf';
    file_put_contents($fontFile, 'fake-font-data');

    config([
        'pdf-studio.fonts' => [
            'inter' => [
                'family' => 'Inter',
                'sources' => [$fontFile],
                'weight' => '400',
                'style' => 'normal',
            ],
        ],
    ]);

    $this->app->forgetInstance(FontRegistry::class);
    $this->app->forgetInstance(FontCssGenerator::class);

    $css = app(FontCssGenerator::class)->generate();

    expect($css)->toContain('@font-face')
        ->and($css)->toContain('font-family:"Inter"')
        ->and($css)->toContain('data:font/ttf;base64,')
        ->and($css)->toContain('font-weight:400')
        ->and($css)->toContain('font-display:swap');

    @unlink($fontFile);
});

it('skips missing or unreadable configured fonts', function () {
    config([
        'pdf-studio.fonts' => [
            'missing' => [
                'family' => 'Missing Font',
                'sources' => ['/tmp/not-a-real-font-file.ttf'],
            ],
        ],
    ]);

    $this->app->forgetInstance(FontRegistry::class);
    $this->app->forgetInstance(FontCssGenerator::class);

    $css = app(FontCssGenerator::class)->generate();

    expect($css)->toBe('');
});
