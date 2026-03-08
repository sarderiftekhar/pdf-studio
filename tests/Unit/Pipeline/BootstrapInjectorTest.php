<?php

use PdfStudio\Laravel\Pipeline\BootstrapInjector;
use PdfStudio\Laravel\DTOs\RenderContext;

it('sets compiledCss to bootstrap CSS content', function () {
    $injector = new BootstrapInjector;
    $context = new RenderContext(compiledHtml: '<div class="container">Hello</div>');

    $result = $injector->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledCss)->toContain('Bootstrap');
    expect($result->compiledCss)->toContain('.container');
});

it('skips injection when compiledHtml is empty', function () {
    $injector = new BootstrapInjector;
    $context = new RenderContext;

    $result = $injector->handle($context, fn ($ctx) => $ctx);

    expect($result->compiledCss)->toBeNull();
});
