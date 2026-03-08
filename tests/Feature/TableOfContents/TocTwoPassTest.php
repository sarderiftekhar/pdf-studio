<?php

use PdfStudio\Laravel\DTOs\TocEntry;
use PdfStudio\Laravel\DTOs\TocOptions;
use PdfStudio\Laravel\TableOfContents\TocExtractor;
use PdfStudio\Laravel\TableOfContents\TocRenderer;

it('extractor and renderer work together', function () {
    $html = '<h1>Title</h1><p>content</p><h2>Section A</h2><p>more</p><h2>Section B</h2>';
    $options = new TocOptions(title: 'Contents');

    $extractor = new TocExtractor;
    $entries = $extractor->extract($html, $options);

    // Simulate page numbers
    $entries[0] = new TocEntry(level: 1, text: 'Title', pageNumber: 2, anchorId: 'toc-0');
    $entries[1] = new TocEntry(level: 2, text: 'Section A', pageNumber: 3, anchorId: 'toc-1');
    $entries[2] = new TocEntry(level: 2, text: 'Section B', pageNumber: 5, anchorId: 'toc-2');

    $renderer = new TocRenderer;
    $tocHtml = $renderer->render($entries, $options);

    expect($tocHtml)->toContain('Contents');
    expect($tocHtml)->toContain('#toc-0');

    // Inject anchors
    $anchoredHtml = $extractor->injectAnchors($html, $options);
    expect($anchoredHtml)->toContain('id="toc-0"');
    expect($anchoredHtml)->toContain('id="toc-1"');

    // Final HTML = TOC + anchored content
    $finalHtml = $tocHtml.$anchoredHtml;
    expect($finalHtml)->toContain('Contents');
    expect($finalHtml)->toContain('id="toc-0"');
});
