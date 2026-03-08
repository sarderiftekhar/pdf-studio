<?php

use PdfStudio\Laravel\DTOs\TocEntry;
use PdfStudio\Laravel\DTOs\TocOptions;

it('creates a TocEntry', function () {
    $entry = new TocEntry(
        level: 1,
        text: 'Introduction',
        pageNumber: 1,
        anchorId: 'toc-1',
    );

    expect($entry->level)->toBe(1);
    expect($entry->text)->toBe('Introduction');
    expect($entry->pageNumber)->toBe(1);
    expect($entry->anchorId)->toBe('toc-1');
});

it('creates TocOptions with defaults', function () {
    $options = new TocOptions;

    expect($options->depth)->toBe(6);
    expect($options->title)->toBe('Table of Contents');
    expect($options->mode)->toBe('auto');
});

it('creates TocOptions with custom values', function () {
    $options = new TocOptions(depth: 3, title: 'Contents', mode: 'explicit');

    expect($options->depth)->toBe(3);
    expect($options->title)->toBe('Contents');
    expect($options->mode)->toBe('explicit');
});
