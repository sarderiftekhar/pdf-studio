<?php

use Illuminate\Support\Facades\Blade;
use PdfStudio\Laravel\Layout\PageNumberGenerator;

it('generates header HTML with page number placeholders', function () {
    $generator = new PageNumberGenerator;
    $html = $generator->header(format: 'Page {page} of {total}', align: 'center');

    expect($html)->toContain('class="pageNumber"')
        ->and($html)->toContain('class="totalPages"')
        ->and($html)->toContain('text-align: center');
});

it('generates footer HTML with page number placeholders', function () {
    $generator = new PageNumberGenerator;
    $html = $generator->footer(format: '{page}/{total}', align: 'right');

    expect($html)->toContain('class="pageNumber"')
        ->and($html)->toContain('class="totalPages"')
        ->and($html)->toContain('text-align: right');
});

it('compiles pageNumber Blade directive', function () {
    $compiled = Blade::compileString('@pageNumber("Page {page} of {total}", "center")');
    expect($compiled)->toContain('PageNumberGenerator');
});
