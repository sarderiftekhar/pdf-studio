<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['view']->addNamespace('pdf-test', __DIR__.'/../stubs/views');
});

it('renders a view and returns PdfResult', function () {
    $result = Pdf::view('pdf-test::simple')
        ->data(['name' => 'Render'])
        ->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toContain('FAKE_PDF')
        ->and($result->content())->toContain('Hello Render')
        ->and($result->driver)->toBe('fake')
        ->and($result->renderTimeMs)->toBeGreaterThanOrEqual(0);
});

it('renders raw HTML and returns PdfResult', function () {
    $result = Pdf::html('<h1>Raw</h1>')->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->content())->toContain('<h1>Raw</h1>');
});

it('downloads a PDF', function () {
    $response = Pdf::html('<p>Download</p>')->download('test.pdf');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->headers->get('Content-Disposition'))->toContain('test.pdf');
});

it('streams a PDF', function () {
    $response = Pdf::html('<p>Stream</p>')->stream('preview.pdf');

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Disposition'))->toContain('preview.pdf');
});

it('saves a PDF to storage', function () {
    Storage::fake('local');

    $storageResult = Pdf::html('<p>Save</p>')->save('output.pdf', 'local');

    expect($storageResult)->toBeInstanceOf(StorageResult::class)
        ->and($storageResult->path)->toBe('output.pdf');

    Storage::disk('local')->assertExists('output.pdf');
});

it('uses a custom driver', function () {
    $result = Pdf::html('<p>Custom</p>')
        ->driver('fake')
        ->render();

    expect($result->driver)->toBe('fake');
});

it('passes render options through to the driver', function () {
    $result = Pdf::html('<p>Options</p>')
        ->format('Letter')
        ->landscape()
        ->margins(top: 20)
        ->render();

    expect($result)->toBeInstanceOf(PdfResult::class);
});
