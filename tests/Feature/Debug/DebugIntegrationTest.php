<?php

use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Facades\Pdf;

beforeEach(function () {
    Storage::fake('local');
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('records debug artifacts when debug is enabled', function () {
    $this->app['config']->set('pdf-studio.debug', true);

    Pdf::html('<h1>Debug Test</h1>')->render();

    $files = Storage::disk('local')->allFiles();

    expect($files)->not->toBeEmpty();
    expect(collect($files)->contains(fn ($f) => str_contains($f, 'compiled.html')))->toBeTrue();
    expect(collect($files)->contains(fn ($f) => str_contains($f, 'metadata.json')))->toBeTrue();
});

it('does not record debug artifacts when debug is disabled', function () {
    $this->app['config']->set('pdf-studio.debug', false);

    Pdf::html('<h1>No Debug</h1>')->render();

    $files = Storage::disk('local')->allFiles();

    expect($files)->toBeEmpty();
});
