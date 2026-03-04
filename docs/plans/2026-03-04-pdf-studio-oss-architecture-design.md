# PDF Studio for Laravel — OSS Architecture Design

**Date:** 2026-03-04
**Scope:** M0–M6 (Open Source Package)
**Status:** Approved

## Decisions

| Decision | Choice |
|---|---|
| Composer package | `pdfstudio/laravel` |
| Namespace | `PdfStudio\Laravel` |
| PHP | 8.1+ |
| Laravel | 10+ |
| Architecture | Pipeline (sequential stages) |
| Chromium driver | Browsershot (spatie) |
| Tailwind compilation | Standalone CLI binary |
| Testing | Pest PHP |

## Package Structure

```
src/
├── PdfStudioServiceProvider.php
├── Facades/
│   └── Pdf.php
├── PdfBuilder.php                # Fluent API entry point
├── Contracts/
│   ├── RendererContract.php      # Driver interface
│   └── CssCompilerContract.php   # Tailwind abstraction
├── Pipeline/
│   ├── RenderPipeline.php        # Orchestrates stages
│   ├── BladeCompiler.php         # Stage 1: template → HTML
│   ├── TailwindCompiler.php      # Stage 2: HTML → CSS
│   ├── CssInjector.php           # Stage 3: CSS → styled HTML
│   └── PdfRenderer.php           # Stage 4: HTML → PDF via driver
├── Drivers/
│   ├── ChromiumDriver.php        # Browsershot wrapper
│   ├── WkhtmlDriver.php          # wkhtmltopdf
│   └── DompdfDriver.php          # dompdf
├── Output/
│   ├── PdfResult.php             # Return type with metadata
│   └── OutputHandler.php         # download/stream/save logic
├── Preview/
│   ├── PreviewController.php
│   └── PreviewMiddleware.php
├── Cache/
│   └── CssCache.php
├── Commands/
│   ├── WarmupCommand.php
│   └── CacheClearCommand.php
└── Exceptions/
    ├── RenderException.php
    └── DriverException.php

config/
└── pdf-studio.php

resources/
└── views/
```

## Fluent API

```php
// Basic download
Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->download('invoice.pdf');

// Full options with storage
Pdf::view('reports.quarterly')
    ->data(['report' => $report])
    ->driver('chromium')
    ->format('A4')
    ->landscape()
    ->margins(top: 20, bottom: 20)
    ->save('reports/q1.pdf', 's3');

// Inline HTML
Pdf::html('<h1>Hello</h1>')
    ->render(); // returns PdfResult

// Stream to browser
Pdf::view('receipts.show')
    ->data(['receipt' => $receipt])
    ->stream('receipt.pdf');
```

## Rendering Pipeline

### Flow

```
PdfBuilder → RenderPipeline → [BladeCompiler → TailwindCompiler → CssInjector → PdfRenderer] → PdfResult
```

### RenderContext DTO

Accumulates state through pipeline stages:

```php
class RenderContext
{
    public ?string $viewName = null;
    public ?string $rawHtml = null;
    public array $data = [];
    public ?string $compiledHtml = null;
    public ?string $compiledCss = null;
    public ?string $styledHtml = null;
    public ?string $pdfContent = null;
    public RenderOptions $options;
}
```

### RenderOptions DTO

```php
class RenderOptions
{
    public string $format = 'A4';
    public bool $landscape = false;
    public array $margins = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
    public bool $printBackground = true;
    public ?string $headerHtml = null;
    public ?string $footerHtml = null;
}
```

## Driver Contract

```php
interface RendererContract
{
    public function render(string $html, RenderOptions $options): string;
    public function supports(): DriverCapabilities;
}

class DriverCapabilities
{
    public bool $landscape;
    public bool $customMargins;
    public bool $headerFooter;
    public bool $printBackground;
    public array $supportedFormats; // ['A4', 'Letter', ...]
}
```

Drivers are registered in config and resolved from the container. Unsupported options throw `DriverException` with actionable messages.

### Drivers

| Driver | Backing Library | Node Required | CSS Fidelity |
|---|---|---|---|
| Chromium | spatie/browsershot | Yes (Puppeteer) | Full |
| wkhtmltopdf | Binary | No | Good |
| dompdf | dompdf/dompdf | No | Limited |

## Tailwind Compilation

### Strategy

1. Extract CSS classes from compiled HTML
2. Write temporary content file for Tailwind CLI to scan
3. Run standalone `tailwindcss` binary to generate minimal CSS
4. Return compiled CSS string

Binary path is configurable. Package provides artisan command to download platform binary.

### Caching

- Cache key: `sha256(template_content . tailwind_config_hash)`
- Stored via Laravel's cache system (configurable store)
- Production: long-lived, cleared via artisan or deploy hook
- Development: auto-invalidates on file modification time change
- Disable entirely via config

### Artisan Commands

- `pdf-studio:warmup` — Pre-compile CSS for registered templates
- `pdf-studio:cache-clear` — Flush CSS cache
- `pdf-studio:install-tailwind` — Download platform-appropriate binary

## Preview System

Route: `/pdf-studio/preview/{template}`

- Disabled by default in all environments (opt-in)
- Protected by configurable middleware (auth by default)
- Sample data via registered "data provider" classes
- Supports `?format=html` and `?format=pdf`
- Payload size limited (default 1MB)

## Debug Utilities

When `pdf-studio.debug` is enabled:

- Dumps HTML, CSS, timing to `storage/pdf-studio/debug/`
- Each render gets timestamped directory with stage artifacts
- Events: `RenderStarting`, `RenderCompleted`, `RenderFailed`
- Events include per-stage timing

## Output & Storage

### PdfResult

```php
class PdfResult
{
    public string $content;
    public string $mimeType;
    public int $bytes;
    public float $renderTimeMs;
    public string $driver;

    public function download(string $filename): Response;
    public function stream(string $filename): StreamedResponse;
    public function save(string $path, ?string $disk = null): StorageResult;
    public function base64(): string;
    public function content(): string;
}
```

### Storage

- `save()` writes to any Laravel filesystem disk
- Returns `StorageResult` with path, disk, bytes, URL
- Overwrite configurable (default: false, appends timestamp)

## Queue Support

```php
Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->queue()
    ->onQueue('pdf-renders')
    ->save('invoices/123.pdf', 's3');
```

- Failed jobs include render context for debugging
- Configurable timeout per driver
- Events: `PdfQueued`, `PdfGenerated`, `PdfFailed`

## Security

- Preview routes gated by environment + middleware
- No user-supplied HTML in preview (templates + data providers only)
- Input data validated and size-limited
- Chromium: `--no-sandbox` disabled by default
- Temporary files cleaned up after render

## Config File

```php
return [
    'default_driver' => env('PDF_STUDIO_DRIVER', 'chromium'),
    'drivers' => [
        'chromium' => ['binary' => null, 'timeout' => 60, 'options' => []],
        'wkhtmltopdf' => ['binary' => '/usr/local/bin/wkhtmltopdf'],
        'dompdf' => ['paper' => 'A4', 'orientation' => 'portrait'],
    ],
    'tailwind' => [
        'binary' => null,
        'config' => null,
        'cache' => ['enabled' => true, 'store' => null, 'ttl' => null],
    ],
    'preview' => [
        'enabled' => env('PDF_STUDIO_PREVIEW', false),
        'prefix' => 'pdf-studio/preview',
        'middleware' => ['web', 'auth'],
        'data_providers' => [],
    ],
    'debug' => env('PDF_STUDIO_DEBUG', false),
    'output' => [
        'default_disk' => null,
        'overwrite' => false,
    ],
    'queue' => [
        'connection' => null,
        'queue' => 'default',
        'timeout' => 120,
        'retries' => 3,
    ],
];
```

## Milestone Mapping

| Milestone | What it delivers |
|---|---|
| M0 | Package skeleton, CI, contribution docs |
| M1 | PdfBuilder, RenderPipeline, contracts, PdfResult |
| M2 | Chromium, wkhtmltopdf, dompdf drivers |
| M3 | TailwindCompiler, CssCache, CLI commands |
| M4 | PreviewController, debug utilities, page-break helpers |
| M5 | Template registry, starter templates |
| M6 | Security hardening, queue support, observability, v1.0 release |
