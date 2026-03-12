<?php

use PdfStudio\Laravel\DTOs\WatermarkOptions;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Testing\PdfFake;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('records renders with view name', function () {
    $fake = new PdfFake($this->app);
    $fake->view('invoices.show')->render();

    $fake->assertRendered();
    $fake->assertRenderedView('invoices.show');
});

it('records renders with html', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Hello</h1>')->render();

    $fake->assertRendered();
    $fake->assertContains('<h1>Hello</h1>');
    $fake->assertContainsHtml('<h1>Hello</h1>');
    $fake->assertContainsText('Hello');
});

it('tracks render count', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<p>1</p>')->render();
    $fake->html('<p>2</p>')->render();

    $fake->assertRenderedCount(2);
});

it('tracks downloaded filenames', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')->download('report.pdf');

    $fake->assertDownloaded('report.pdf');
});

it('tracks save path and disk', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')->save('reports/test.pdf', 's3');

    $fake->assertSavedTo('reports/test.pdf', 's3');
    $fake->assertSaved('reports/test.pdf', 's3');
});

it('tracks save path without disk', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')->save('reports/test.pdf');

    $fake->assertSavedTo('reports/test.pdf');
});

it('tracks driver used', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')->driver('dompdf')->render();

    $fake->assertDriverWas('dompdf');
});

it('tracks merges', function () {
    $fake = new PdfFake($this->app);
    $fake->merge(['file1.pdf', 'file2.pdf']);

    $fake->assertMerged();
    $fake->assertMergedCount(1);
});

it('detects watermark via options', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>');
    $fake->getContext()->options->watermark = new WatermarkOptions(text: 'DRAFT');
    $fake->render();

    $fake->assertWatermarked();
});

it('detects protection via options', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>');
    $fake->getContext()->options->userPassword = 'secret';
    $fake->render();

    $fake->assertProtected();
});

it('asserts nothing rendered when no renders', function () {
    $fake = new PdfFake($this->app);

    $fake->assertNothingRendered();
});

it('returns PdfResult from render', function () {
    $fake = new PdfFake($this->app);
    $result = $fake->html('<h1>Test</h1>')->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toContain('FAKE_PDF')
        ->and($result->renderTimeMs)->toBe(0.0);
});

it('returns the last render payload for inspection', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<p>First</p>')->render();
    $fake->view('reports.monthly')
        ->data(['month' => 'March'])
        ->driver('dompdf')
        ->metadata(['title' => 'Monthly Report'])
        ->attachFile('/tmp/report.csv', 'report.csv', 'text/csv')
        ->pdfVariant('pdf/a-2b')
        ->render();

    $lastRender = $fake->lastRender();

    expect($lastRender['view'])->toBe('reports.monthly')
        ->and($lastRender['driver'])->toBe('dompdf')
        ->and($lastRender['data'])->toBe(['month' => 'March'])
        ->and($lastRender['options']->metadata)->toBe(['title' => 'Monthly Report'])
        ->and($lastRender['options']->attachments)->toBe([
            ['name' => 'report.csv', 'path' => '/tmp/report.csv', 'mime' => 'text/csv'],
        ])
        ->and($lastRender['options']->pdfVariant)->toBe('pdf/a-2b')
        ->and($lastRender['output'])->toContain('reports.monthly');
});

it('asserts metadata attachments and pdf variant', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')
        ->metadata(['title' => 'Report'])
        ->attachFile('/tmp/report.csv', 'report.csv', 'text/csv')
        ->pdfVariant('pdf/ua-1')
        ->render();

    $fake->assertHasMetadata(['title' => 'Report']);
    $fake->assertHasAttachment('/tmp/report.csv', 'report.csv');
    $fake->assertPdfVariant('pdf/ua-1');
});

it('fails assertRenderedView when view not rendered', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')->render();

    expect(fn () => $fake->assertRenderedView('missing.view'))
        ->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
});

it('fails assertNothingRendered when renders exist', function () {
    $fake = new PdfFake($this->app);
    $fake->html('<h1>Test</h1>')->render();

    expect(fn () => $fake->assertNothingRendered())
        ->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
});
