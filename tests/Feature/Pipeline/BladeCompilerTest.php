<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Exceptions\RenderException;
use PdfStudio\Laravel\Pipeline\BladeCompiler;

beforeEach(function () {
    $this->app['view']->addNamespace('pdf-test', __DIR__.'/../../stubs/views');
});

it('compiles a blade view with data', function () {
    $compiler = app(BladeCompiler::class);
    $context = new RenderContext(
        viewName: 'pdf-test::simple',
        data: ['name' => 'World'],
    );

    $result = $compiler->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledHtml)->toContain('Hello World');
});

it('uses raw HTML when no view is set', function () {
    $compiler = app(BladeCompiler::class);
    $context = new RenderContext(
        rawHtml: '<h1>Raw Content</h1>',
    );

    $result = $compiler->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledHtml)->toBe('<h1>Raw Content</h1>');
});

it('throws when neither view nor HTML is set', function () {
    $compiler = app(BladeCompiler::class);
    $context = new RenderContext;

    $compiler->handle($context, fn ($ctx) => $ctx);
})->throws(RenderException::class, 'No view or HTML content provided');

it('passes context to the next stage', function () {
    $compiler = app(BladeCompiler::class);
    $context = new RenderContext(rawHtml: '<p>Test</p>');
    $nextCalled = false;

    $compiler->handle($context, function ($ctx) use (&$nextCalled) {
        $nextCalled = true;

        return $ctx;
    });

    expect($nextCalled)->toBeTrue();
});
