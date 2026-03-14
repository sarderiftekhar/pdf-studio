<?php

use PdfStudio\Laravel\DTOs\TocEntry;
use PdfStudio\Laravel\DTOs\TocOptions;
use PdfStudio\Laravel\TableOfContents\TocRenderer;

it('renders TOC HTML from entries', function () {
    $entries = [
        new TocEntry(level: 1, text: 'Introduction', pageNumber: 1, anchorId: 'toc-0'),
        new TocEntry(level: 2, text: 'Background', pageNumber: 3, anchorId: 'toc-1'),
        new TocEntry(level: 2, text: 'Methods', pageNumber: 5, anchorId: 'toc-2'),
    ];

    $renderer = new TocRenderer;
    $html = $renderer->render($entries, new TocOptions(title: 'Contents'));

    expect($html)->toContain('Contents');
    expect($html)->toContain('Introduction');
    expect($html)->toContain('Background');
    expect($html)->toContain('#toc-0');
    expect($html)->toContain('toc-level-1');
    expect($html)->toContain('toc-level-2');
});

it('includes page numbers', function () {
    $entries = [
        new TocEntry(level: 1, text: 'Chapter 1', pageNumber: 5, anchorId: 'toc-0'),
    ];

    $renderer = new TocRenderer;
    $html = $renderer->render($entries, new TocOptions);

    expect($html)->toContain('5');
});
