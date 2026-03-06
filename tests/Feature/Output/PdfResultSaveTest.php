<?php

use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;

beforeEach(function () {
    Storage::fake('local');
});

it('saves PDF to a disk', function () {
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    $storageResult = $result->save('reports/test.pdf', 'local');

    expect($storageResult)->toBeInstanceOf(StorageResult::class)
        ->and($storageResult->path)->toBe('reports/test.pdf')
        ->and($storageResult->disk)->toBe('local')
        ->and($storageResult->bytes)->toBe(9);

    Storage::disk('local')->assertExists('reports/test.pdf');
});

it('saves to default disk when none specified', function () {
    Storage::fake();
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    $storageResult = $result->save('output.pdf');

    expect($storageResult->path)->toBe('output.pdf');
    Storage::assertExists('output.pdf');
});

it('returns storage result with metadata', function () {
    $result = new PdfResult(content: 'PDF_BYTES', driver: 'fake', renderTimeMs: 0);

    $storageResult = $result->save('test.pdf', 'local');

    expect($storageResult->path)->toBe('test.pdf')
        ->and($storageResult->disk)->toBe('local')
        ->and($storageResult->bytes)->toBe(9)
        ->and($storageResult->mimeType)->toBe('application/pdf');
});
