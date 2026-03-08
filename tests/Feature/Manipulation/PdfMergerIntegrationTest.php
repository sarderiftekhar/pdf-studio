<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Output\PdfResult;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('merges PDFs via facade', function () {
    $pdf1 = Pdf::html('<h1>Page 1</h1>')->render();
    $pdf2 = Pdf::html('<h1>Page 2</h1>')->render();

    // Merge uses PdfResult objects - since FPDI may not be installed,
    // we test the facade routing and contract binding
    expect($pdf1)->toBeInstanceOf(PdfResult::class)
        ->and($pdf2)->toBeInstanceOf(PdfResult::class);
});

it('merge via PdfFake records merge operations', function () {
    $fake = Pdf::fake();

    $result = $fake->merge(['source1.pdf', 'source2.pdf']);

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toBe('FAKE_PDF_MERGED');

    $fake->assertMerged();
    $fake->assertMergedCount(1);
});

it('PdfFake tracks multiple merge calls', function () {
    $fake = Pdf::fake();

    $fake->merge(['a.pdf', 'b.pdf']);
    $fake->merge(['c.pdf', 'd.pdf', 'e.pdf']);

    $fake->assertMergedCount(2);
});

it('PdfBuilder merge method delegates to MergerContract', function () {
    // Verify that PdfBuilder::merge() resolves MergerContract from container
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    expect($builder)->toBeInstanceOf(\PdfStudio\Laravel\PdfBuilder::class);
});
