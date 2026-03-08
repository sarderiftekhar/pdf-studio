<?php

use PdfStudio\Laravel\PdfBuilder;

it('sets cssFramework to bootstrap via fluent API', function () {
    $builder = app(PdfBuilder::class);
    $builder->html('<div class="container">Test</div>')->bootstrap();

    expect($builder->getContext()->cssFramework)->toBe('bootstrap');
});

it('sets cssFramework to tailwind via fluent API', function () {
    $builder = app(PdfBuilder::class);
    $builder->html('<div class="flex">Test</div>')->tailwind();

    expect($builder->getContext()->cssFramework)->toBe('tailwind');
});
