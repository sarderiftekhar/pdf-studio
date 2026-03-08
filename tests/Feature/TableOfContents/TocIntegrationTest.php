<?php

use PdfStudio\Laravel\DTOs\TocOptions;
use PdfStudio\Laravel\PdfBuilder;

it('withTableOfContents sets toc options on context', function () {
    $builder = app(PdfBuilder::class);
    $builder->html('<h1>Test</h1>')->withTableOfContents();

    $options = $builder->getContext()->options;

    expect($options->tocOptions)->toBeInstanceOf(TocOptions::class);
    expect($options->tocOptions->depth)->toBe(6);
    expect($options->tocOptions->title)->toBe('Table of Contents');
});

it('withTableOfContents accepts custom options', function () {
    $builder = app(PdfBuilder::class);
    $builder->html('<h1>Test</h1>')->withTableOfContents(depth: 3, title: 'Contents');

    expect($builder->getContext()->options->tocOptions->depth)->toBe(3);
    expect($builder->getContext()->options->tocOptions->title)->toBe('Contents');
});

it('withBookmarks sets bookmarks flag', function () {
    $builder = app(PdfBuilder::class);
    $builder->html('<h1>Test</h1>')->withBookmarks();

    expect($builder->getContext()->options->tocOptions->bookmarks)->toBeTrue();
});

it('toc config is loaded', function () {
    expect(config('pdf-studio.toc'))->toBeArray();
    expect(config('pdf-studio.toc.depth'))->toBe(6);
});
