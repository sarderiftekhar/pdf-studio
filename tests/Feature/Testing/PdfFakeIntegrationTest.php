<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Testing\PdfFake;

it('swaps facade with fake via Pdf::fake()', function () {
    $fake = Pdf::fake();

    expect($fake)->toBeInstanceOf(PdfFake::class);
    expect(Pdf::getFacadeRoot())->toBeInstanceOf(PdfFake::class);
});

it('records renders through facade', function () {
    $fake = Pdf::fake();

    Pdf::html('<h1>Invoice</h1>')->render();

    $fake->assertRendered();
    $fake->assertRenderedCount(1);
    $fake->assertContains('<h1>Invoice</h1>');
});

it('records downloads through facade', function () {
    $fake = Pdf::fake();

    Pdf::html('<h1>Report</h1>')->download('report.pdf');

    $fake->assertDownloaded('report.pdf');
});

it('records saves through facade', function () {
    $fake = Pdf::fake();

    Pdf::html('<h1>Data</h1>')->save('output/report.pdf', 'local');

    $fake->assertSavedTo('output/report.pdf', 'local');
});

it('tracks driver through facade', function () {
    $fake = Pdf::fake();

    Pdf::html('<h1>Test</h1>')->driver('dompdf')->render();

    $fake->assertDriverWas('dompdf');
});

it('asserts nothing when no activity', function () {
    $fake = Pdf::fake();

    $fake->assertNothingRendered();
});
