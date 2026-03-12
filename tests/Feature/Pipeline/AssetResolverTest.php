<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Exceptions\RenderException;
use PdfStudio\Laravel\Pipeline\AssetResolver;

it('inlines local image sources as data uris', function () {
    $imagePath = tempnam(sys_get_temp_dir(), 'pdfstudio_asset_').'.png';
    file_put_contents($imagePath, 'fake-image');

    $resolver = app(AssetResolver::class);
    $context = new RenderContext(
        compiledHtml: '<html><body><img src="'.$imagePath.'" alt="Logo"></body></html>',
    );

    $result = $resolver->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledHtml)->toContain('data:image/png;base64,')
        ->and($result->compiledHtml)->toContain('alt="Logo"');

    @unlink($imagePath);
});

it('replaces local stylesheet links with inline style tags', function () {
    $cssPath = tempnam(sys_get_temp_dir(), 'pdfstudio_asset_css_').'.css';
    file_put_contents($cssPath, 'body { color: red; }');

    $resolver = app(AssetResolver::class);
    $context = new RenderContext(
        compiledHtml: '<html><head><link rel="stylesheet" href="'.$cssPath.'"></head><body>Test</body></html>',
    );

    $result = $resolver->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledHtml)->toContain('<style>body { color: red; }</style>')
        ->and($result->compiledHtml)->not->toContain('<link rel="stylesheet"');

    @unlink($cssPath);
});

it('throws when remote assets are disabled and a remote image is present', function () {
    config(['pdf-studio.assets.allow_remote' => false]);

    $resolver = app(AssetResolver::class);
    $context = new RenderContext(
        compiledHtml: '<html><body><img src="https://example.com/logo.png"></body></html>',
    );

    $resolver->handle($context, fn ($ctx) => $ctx);
})->throws(RenderException::class, 'Remote asset loading is disabled');

it('leaves remote assets alone when remote loading is enabled', function () {
    config(['pdf-studio.assets.allow_remote' => true]);

    $resolver = app(AssetResolver::class);
    $context = new RenderContext(
        compiledHtml: '<html><body><img src="https://example.com/logo.png"></body></html>',
    );

    $result = $resolver->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledHtml)->toContain('https://example.com/logo.png');
});

it('allows remote assets from explicitly allowed hosts', function () {
    config([
        'pdf-studio.assets.allow_remote' => true,
        'pdf-studio.assets.allowed_hosts' => ['assets.example.com'],
    ]);

    $resolver = app(AssetResolver::class);
    $context = new RenderContext(
        compiledHtml: '<html><body><img src="https://assets.example.com/logo.png"></body></html>',
    );

    $result = $resolver->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledHtml)->toContain('https://assets.example.com/logo.png');
});

it('blocks remote assets from hosts outside the allowlist', function () {
    config([
        'pdf-studio.assets.allow_remote' => true,
        'pdf-studio.assets.allowed_hosts' => ['assets.example.com'],
    ]);

    $resolver = app(AssetResolver::class);
    $context = new RenderContext(
        compiledHtml: '<html><body><img src="https://cdn.example.com/logo.png"></body></html>',
    );

    $resolver->handle($context, fn ($ctx) => $ctx);
})->throws(RenderException::class, 'Remote asset host is not allowed');
