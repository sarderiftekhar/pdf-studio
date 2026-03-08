<?php

use PdfStudio\Laravel\TableOfContents\TocExtractor;
use PdfStudio\Laravel\DTOs\TocOptions;

it('extracts headings from HTML', function () {
    $html = '<h1>Title</h1><p>content</p><h2>Section A</h2><h3>Subsection</h3><h2>Section B</h2>';
    $extractor = new TocExtractor;
    $entries = $extractor->extract($html, new TocOptions);

    expect($entries)->toHaveCount(4);
    expect($entries[0]->level)->toBe(1);
    expect($entries[0]->text)->toBe('Title');
    expect($entries[1]->level)->toBe(2);
    expect($entries[1]->text)->toBe('Section A');
});

it('respects depth option', function () {
    $html = '<h1>Title</h1><h2>Section</h2><h3>Sub</h3><h4>Deep</h4>';
    $extractor = new TocExtractor;
    $entries = $extractor->extract($html, new TocOptions(depth: 2));

    expect($entries)->toHaveCount(2);
});

it('excludes headings with data-toc=false', function () {
    $html = '<h1>Title</h1><h2 data-toc="false">Skip This</h2><h2>Include</h2>';
    $extractor = new TocExtractor;
    $entries = $extractor->extract($html, new TocOptions);

    expect($entries)->toHaveCount(2);
    expect($entries[1]->text)->toBe('Include');
});

it('explicit mode only includes data-toc headings', function () {
    $html = '<h1>Skip</h1><h2 data-toc>Include A</h2><h2>Skip B</h2><h3 data-toc>Include C</h3>';
    $extractor = new TocExtractor;
    $entries = $extractor->extract($html, new TocOptions(mode: 'explicit'));

    expect($entries)->toHaveCount(2);
    expect($entries[0]->text)->toBe('Include A');
    expect($entries[1]->text)->toBe('Include C');
});

it('injects anchor IDs into headings', function () {
    $html = '<h1>Title</h1><h2>Section</h2>';
    $extractor = new TocExtractor;
    $entries = $extractor->extract($html, new TocOptions);

    expect($entries[0]->anchorId)->toBe('toc-0');
    expect($entries[1]->anchorId)->toBe('toc-1');
});
