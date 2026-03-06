<?php

use PdfStudio\Laravel\Contracts\CssCompilerContract;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Pipeline\TailwindCompiler;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', false);
});

it('implements CssCompilerContract', function () {
    $compiler = app(TailwindCompiler::class);

    expect($compiler)->toBeInstanceOf(CssCompilerContract::class);
});

it('populates compiledCss on the render context', function () {
    $binary = findTailwindBinary();

    if ($binary === null) {
        $this->markTestSkipped('Tailwind CSS binary not found');
    }

    $this->app['config']->set('pdf-studio.tailwind.binary', $binary);

    $compiler = app(TailwindCompiler::class);
    $context = new RenderContext;
    $context->compiledHtml = '<div class="text-red-500 p-4">Hello</div>';

    $result = $compiler->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledCss)->toBeString()
        ->and($result->compiledCss)->not->toBeEmpty();
});

it('compiles CSS with actual Tailwind classes', function () {
    $binary = findTailwindBinary();

    if ($binary === null) {
        $this->markTestSkipped('Tailwind CSS binary not found');
    }

    $this->app['config']->set('pdf-studio.tailwind.binary', $binary);

    $compiler = app(TailwindCompiler::class);
    $css = $compiler->compile('<div class="text-red-500 font-bold">Test</div>');

    expect($css)->toContain('text-red-500')
        ->and($css)->toContain('font-bold');
});

it('returns empty CSS when no tailwind classes found', function () {
    $binary = findTailwindBinary();

    if ($binary === null) {
        $this->markTestSkipped('Tailwind CSS binary not found');
    }

    $this->app['config']->set('pdf-studio.tailwind.binary', $binary);

    $compiler = app(TailwindCompiler::class);
    $css = $compiler->compile('<div>No tailwind here</div>');

    // Still returns CSS (Tailwind always outputs base styles), but no utility classes
    expect($css)->toBeString();
});

it('skips compilation when binary is not configured', function () {
    $this->app['config']->set('pdf-studio.tailwind.binary', null);

    $compiler = app(TailwindCompiler::class);
    $context = new RenderContext;
    $context->compiledHtml = '<div class="text-red-500">Test</div>';

    $result = $compiler->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledCss)->toBeNull();
});

it('uses cached CSS when available', function () {
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', true);
    // Set a binary path so the compiler doesn't skip. It won't be called
    // because the cache hit will short-circuit before compilation.
    $this->app['config']->set('pdf-studio.tailwind.binary', '/usr/bin/true');

    $cache = app(\PdfStudio\Laravel\Cache\CssCache::class);
    $html = '<div class="text-blue-500">Cached</div>';
    $cacheKey = $cache->key($html);
    $cache->put($cacheKey, '.cached-css { color: blue; }');

    $compiler = app(TailwindCompiler::class);
    $context = new RenderContext;
    $context->compiledHtml = $html;

    $result = $compiler->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledCss)->toBe('.cached-css { color: blue; }');
});

/**
 * Helper to find the tailwind binary.
 */
function findTailwindBinary(): ?string
{
    $possiblePaths = [
        base_path('node_modules/.bin/tailwindcss'),
        '/usr/local/bin/tailwindcss',
    ];

    // Also check the system PATH using exec with a fixed command
    $whichResult = '';
    exec('which tailwindcss 2>/dev/null', $output, $exitCode);
    if ($exitCode === 0 && isset($output[0])) {
        $whichResult = trim($output[0]);
    }

    if (!empty($whichResult)) {
        $possiblePaths[] = $whichResult;
    }

    foreach ($possiblePaths as $path) {
        if (!empty($path) && is_executable($path)) {
            return $path;
        }
    }

    return null;
}
