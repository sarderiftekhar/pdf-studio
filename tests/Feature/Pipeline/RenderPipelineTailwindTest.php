<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Pipeline\RenderPipeline;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', false);
});

it('runs pipeline without tailwind when binary is not configured', function () {
    $this->app['config']->set('pdf-studio.tailwind.binary', null);

    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(rawHtml: '<div class="text-red-500">No Tailwind</div>');

    $result = $pipeline->run($context);

    expect($result->pdfContent)->toContain('FAKE_PDF')
        ->and($result->compiledCss)->toBeNull();
});

it('injects compiled CSS into pipeline when tailwind binary is configured', function () {
    $binary = findTailwindBinaryForPipeline();

    if ($binary === null) {
        $this->markTestSkipped('Tailwind CSS binary not found');
    }

    $this->app['config']->set('pdf-studio.tailwind.binary', $binary);

    $pipeline = app(RenderPipeline::class);
    $context = new RenderContext(rawHtml: '<div class="text-red-500 p-4">With Tailwind</div>');

    $result = $pipeline->run($context);

    expect($result->compiledCss)->not->toBeNull()
        ->and($result->styledHtml)->toContain('<style>')
        ->and($result->pdfContent)->toContain('FAKE_PDF');
});

it('uses cached CSS on second run', function () {
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', true);

    $binary = findTailwindBinaryForPipeline();

    if ($binary === null) {
        $this->markTestSkipped('Tailwind CSS binary not found');
    }

    $this->app['config']->set('pdf-studio.tailwind.binary', $binary);

    $html = '<div class="font-bold text-lg">Cached Run</div>';

    // First run — compiles
    $pipeline1 = app(RenderPipeline::class);
    $result1 = $pipeline1->run(new RenderContext(rawHtml: $html));

    // Second run — should hit cache
    $pipeline2 = app(RenderPipeline::class);
    $result2 = $pipeline2->run(new RenderContext(rawHtml: $html));

    expect($result1->compiledCss)->toBe($result2->compiledCss);
});

function findTailwindBinaryForPipeline(): ?string
{
    $paths = [
        base_path('node_modules/.bin/tailwindcss'),
        '/usr/local/bin/tailwindcss',
    ];

    // Check system PATH
    $whichResult = '';
    exec('which tailwindcss 2>/dev/null', $output, $exitCode);
    if ($exitCode === 0 && isset($output[0])) {
        $whichResult = trim($output[0]);
    }

    if (!empty($whichResult)) {
        $paths[] = $whichResult;
    }

    foreach ($paths as $path) {
        if (!empty($path) && is_executable($path)) {
            return $path;
        }
    }

    return null;
}
