# Existing PDF Workflows

This guide covers PDF Studio's APIs for inspecting and editing PDFs that already exist, whether they come from rendered output, uploads, storage, or external systems.

## Overview

The existing-PDF workflow surface falls into three groups:

- inspect: validate, summarize, read metadata, and plan work without changing the document
- edit pages: split, chunk, reorder, rotate, remove, flatten, and embed files
- choose source type: use byte-string helpers for in-memory PDFs or `*File()` helpers for storage-backed workflows

## Inspect

Use the inspection APIs before you mutate uploaded or externally generated PDFs:

```php
$bytes = file_get_contents(storage_path('app/reports/annual.pdf'));

$isPdf = Pdf::isPdf($bytes);

$summary = Pdf::inspectPdf($bytes);
// ['valid' => true, 'page_count' => 42, 'byte_size' => 182304]

$metadata = Pdf::readPdfMetadata($bytes);

Pdf::assertPdf($bytes, 'uploaded report');
```

File-based variants avoid reading the file in your own code:

```php
$isPdf = Pdf::isPdfFile(storage_path('app/reports/annual.pdf'));

$summary = Pdf::inspectPdfFile(storage_path('app/reports/annual.pdf'));

$metadata = Pdf::readPdfMetadataFile(storage_path('app/reports/annual.pdf'));

Pdf::assertPdfFile(storage_path('app/reports/annual.pdf'), 'stored annual report');
```

Use `isPdf*()` when you want branching behavior. Use `assertPdf*()` when invalid input should fail the job immediately.

## Plan

For large-document pipelines, plan first and mutate second:

```php
$pageCount = Pdf::pageCount($bytes);

$ranges = Pdf::chunkRanges($bytes, 25);

$plan = Pdf::chunkPlan($bytes, 25);
// [
//   ['index' => 1, 'start' => 1, 'end' => 25, 'pages' => 25, 'range' => '1-25'],
//   ...
// ]
```

File-based planning:

```php
$pageCount = Pdf::pageCountFile(storage_path('app/reports/annual.pdf'));

$ranges = Pdf::chunkRangesFile(storage_path('app/reports/annual.pdf'), 25);

$plan = Pdf::chunkPlanFile(storage_path('app/reports/annual.pdf'), 25);
```

Use `chunkRanges*()` for lightweight page-range strings. Use `chunkPlan*()` when you need structured metadata for naming, queue payloads, or progress tracking.

## Edit Pages

Split or chunk a PDF into smaller pieces:

```php
$parts = Pdf::split($bytes, ['1-2', '3-5']);

$chunks = Pdf::chunk($bytes, 25);
```

Reorder, rotate, or remove pages:

```php
$reordered = Pdf::reorderPages($bytes, [3, 1, 2]);

$rotated = Pdf::rotatePages($bytes, 90, [1, 3]);

$trimmed = Pdf::removePages($bytes, [2, 4]);
```

Flatten or embed files:

```php
$flattened = Pdf::flattenPdf($bytes);

$embedded = Pdf::embedFiles($bytes, [[
    'path' => storage_path('app/reports/source.csv'),
    'name' => 'source.csv',
    'mime' => 'text/csv',
]]);
```

Every one of those has a file-based variant:

```php
Pdf::splitFile($path, ['1-2', '3-5']);
Pdf::chunkFile($path, 25);
Pdf::reorderPagesFile($path, [3, 1, 2]);
Pdf::rotatePagesFile($path, 180);
Pdf::removePagesFile($path, [2, 4]);
Pdf::flattenPdfFile($path);
Pdf::readPdfMetadataFile($path);
Pdf::embedFilesIntoFile($path, $files);
```

## Suggested Flow

For uploaded or third-party PDFs:

1. `inspectPdf()` or `inspectPdfFile()`
2. `assertPdf()` or `assertPdfFile()` if the workflow should hard-fail on invalid input
3. `pageCount()` and `chunkPlan()` if you need staged execution
4. apply the editing operation you actually need

For large reports generated in your own app:

1. render the PDF once
2. inspect and size it
3. chunk or split it if transport/storage/review constraints require that
4. reorder/rotate/remove/flatten/embed as a final post-processing step

## Tooling

These helpers depend on different underlying tools:

- basic parser-backed: `readPdfMetadata`
- FPDI-backed: `pageCount`, `chunk`, `chunkRanges`, `chunkPlan`, `split`, `reorderPages`, `rotatePages`, `removePages`
- pdftk-backed: `flattenPdf`
- Gotenberg-backed: `embedFiles`

Run `php artisan pdf-studio:doctor` to see which existing-PDF helpers are currently available in your environment.
