# PDF Studio for Laravel

Design, preview, and generate PDFs using HTML and TailwindCSS in Laravel.

[![Tests](https://img.shields.io/badge/tests-367%20passing-brightgreen)](tests)
[![PHPStan](https://img.shields.io/badge/phpstan-level%206-blue)](phpstan.neon)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

> **Free and open source.** All features — including template versioning, workspaces, the visual builder, and the hosted rendering API — are available under the MIT license with no key, subscription, or license fee required.

📖 **[Full User Guide](docs/user-guide.html)** — detailed examples, page layouts, framework integrations (Livewire, Vue, React, Node.js, vanilla JS), and troubleshooting.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Drivers](#drivers)
- [Template Registry](#template-registry)
- [Blade Directives](#blade-directives)
- [Queue / Async Rendering](#queue--async-rendering)
- [Preview Routes](#preview-routes)
- [Tailwind CSS](#tailwind-css)
- [Template Versioning](#template-versioning)
- [Workspaces & Access Control](#workspaces--access-control)
- [Visual Builder](#visual-builder)
- [SaaS: Hosted Rendering API](#saas-hosted-rendering-api)
- [SaaS: Usage Metering](#saas-usage-metering)
- [SaaS: Analytics](#saas-analytics)
- [PDF Merging](#pdf-merging)
- [PDF Post-Processing](#pdf-post-processing)
- [Watermarking](#watermarking)
- [Password Protection](#password-protection)
- [AcroForm Fill](#acroform-fill)
- [Livewire / Filament](#livewire--filament)
- [Render Caching](#render-caching)
- [Auto-Height Paper](#auto-height-paper)
- [Header/Footer Per-Page Control](#headerfooter-per-page-control)
- [Diagnostics](#diagnostics)
- [Configuration Reference](#configuration-reference)
- [Testing](#testing)

---

## Requirements

- **PHP** >= 8.1
- **Laravel** 10.x, 11.x, or 12.x

### Optional Dependencies

| Driver | Package | Required For |
|--------|---------|-------------|
| Chromium | `spatie/browsershot` ^5.2 | Full CSS/TailwindCSS fidelity (recommended) |
| Cloudflare | Cloudflare Browser Rendering | Managed remote Chromium rendering |
| Gotenberg | Self-hosted Gotenberg service | Remote/self-hosted Chromium rendering |
| WeasyPrint | System `weasyprint` binary | Print-native rendering, attachments, PDF variants |
| dompdf | `dompdf/dompdf` ^2.0\|^3.0 | Zero external dependencies, limited CSS |
| wkhtmltopdf | System binary | Good CSS fidelity, no Node.js needed |

**PDF Manipulation (optional):**

| Package | Required For |
|---------|-------------|
| `setasign/fpdi` ^2.3 | PDF merging, watermarking |
| `mikehaertl/php-pdftk` ^4.0 | AcroForm fill, password protection |

> **Note:** The Chromium driver requires Node.js and a Chromium/Chrome binary on the server.

---

## Installation

```bash
composer require sarder/pdfstudio
```

Publish the config file:

```bash
php artisan vendor:publish --tag=pdf-studio-config
```

If you're using Pro or SaaS features, publish and run migrations:

```bash
php artisan vendor:publish --tag=pdf-studio-migrations
php artisan migrate
```

---

## Quick Start

The `Pdf` facade is auto-discovered. No manual registration needed.

```php
use PdfStudio\Laravel\Facades\Pdf;

// Download immediately
return Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->download('invoice.pdf');

// Stream inline in browser
return Pdf::view('reports.quarterly')
    ->data(['report' => $report])
    ->inline('report.pdf');

// Save to Storage
Pdf::view('statements.monthly')
    ->data(['account' => $account])
    ->driver('chromium')
    ->format('A4')
    ->landscape()
    ->save('statements/2024-01.pdf', 's3');

// Render from inline HTML
$result = Pdf::html('<h1>Hello World</h1>')->render();
echo $result->bytes;        // file size in bytes
echo $result->renderTimeMs; // render duration
```

---

## Drivers

Detailed driver selection and troubleshooting guide:

- [Driver Guide](docs/driver-guide.md)

| Driver | Runtime | Node | CSS Fidelity | Best For |
|--------|---------|------|--------------|----------|
| `chromium` (default) | Local Chrome + Browsershot | Yes | Full | High-fidelity local rendering |
| `cloudflare` | Cloudflare Browser Rendering | No local Node | Full | Managed remote rendering |
| `gotenberg` | Remote Gotenberg service | No local Node | Full | Self-hosted remote rendering |
| `weasyprint` | System `weasyprint` binary | No | Print-native | Tagged PDFs, attachments, PDF variants |
| `wkhtmltopdf` | System binary | No | Good | Legacy compatibility |
| `dompdf` | `dompdf/dompdf` | No | Limited | Zero external services |
| `fake` | Built-in | No | N/A | Testing only |

Install your preferred driver:

```bash
# Chromium (recommended for TailwindCSS)
composer require spatie/browsershot

# dompdf (zero external dependencies)
composer require dompdf/dompdf
```

Set the default in config:

```php
// config/pdf-studio.php
'default_driver' => 'chromium',
```

Switch per-render:

```php
Pdf::view('report')->driver('dompdf')->download('report.pdf');
```

Remote driver examples:

```php
Pdf::view('report')->driver('cloudflare')->download('report.pdf');
Pdf::view('report')->driver('gotenberg')->download('report.pdf');
Pdf::view('report')->driver('weasyprint')->pdfVariant('pdf/a-3u')->download('report.pdf');
```

Cloudflare config:

```php
'drivers' => [
    'cloudflare' => [
        'account_id' => env('PDF_STUDIO_CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('PDF_STUDIO_CLOUDFLARE_API_TOKEN'),
    ],
],
```

Gotenberg config:

```php
'drivers' => [
    'gotenberg' => [
        'url' => env('PDF_STUDIO_GOTENBERG_URL', 'http://127.0.0.1:3000'),
    ],
],
```

WeasyPrint config:

```php
'drivers' => [
    'weasyprint' => [
        'binary' => env('PDF_STUDIO_WEASYPRINT_BINARY', 'weasyprint'),
    ],
],
```

### Modern Render Options

```php
Pdf::view('reports.quarterly')
    ->driver('chromium')
    ->pageRanges('1-3')
    ->preferCssPageSize()
    ->scale(0.95)
    ->waitForFonts()
    ->waitForNetworkIdle()
    ->waitDelay(750)
    ->waitForSelector('#report-ready', ['visible' => true])
    ->taggedPdf()
    ->outline()
    ->metadata([
        'title' => 'Quarterly Report',
        'author' => 'PDF Studio',
    ])
    ->download('quarterly-report.pdf');
```

---

## Template Registry

Register named templates with default options and data providers:

```php
// config/pdf-studio.php
'templates' => [
    'invoice' => [
        'view'            => 'pdf.invoice',
        'description'     => 'Customer invoice',
        'default_options' => ['format' => 'A4'],
        'data_provider'   => App\Pdf\InvoiceDataProvider::class,
    ],
],
```

Use a registered template:

```php
Pdf::template('invoice')->data(['id' => 123])->download('invoice.pdf');
```

List all registered templates:

```bash
php artisan pdf-studio:templates
```

---

## Blade Directives

```blade
{{-- Force a page break --}}
@pageBreak

{{-- Page break before content --}}
@pageBreakBefore

{{-- Prevent content from splitting across pages --}}
@avoidBreak
    <div>Keep this together on one page</div>
@endAvoidBreak

{{-- Show/hide based on condition (CSS-based, prints correctly) --}}
@showIf($invoice->isPaid())
    <span>PAID</span>
@endShowIf

{{-- Wrap content to avoid mid-section breaks --}}
@keepTogether
    <table>...</table>
@endKeepTogether

{{-- Page number footer (Chromium only) --}}
@pageNumber(['format' => 'Page {page} of {total}'])
```

---

## Queue / Async Rendering

Dispatch a render job to a queue:

```php
use PdfStudio\Laravel\Jobs\RenderPdfJob;

RenderPdfJob::dispatch(
    view:       'invoices.show',
    data:       ['invoice' => $invoice->toArray()],
    outputPath: 'invoices/inv-001.pdf',
    disk:       's3',
    driver:     'chromium',
);
```

Batch rendering:

```php
Pdf::batch([
    ['view' => 'invoices.show', 'data' => $inv1->toArray(), 'outputPath' => 'inv-1.pdf'],
    ['view' => 'invoices.show', 'data' => $inv2->toArray(), 'outputPath' => 'inv-2.pdf'],
], driver: 'dompdf', disk: 's3');
```

Compose multiple sections independently and merge them into one PDF:

```php
$result = Pdf::compose([
    ['html' => '<h1>Cover</h1>'],
    [
        'view' => 'pdf.invoice',
        'data' => ['invoice' => $invoice],
        'options' => [
            'format' => 'A4',
            'metadata' => ['title' => 'Invoice section'],
        ],
    ],
], driver: 'chromium');
```

---

## Preview Routes

Enable browser-based template preview (disabled in production by default):

```php
// config/pdf-studio.php
'preview' => [
    'enabled'     => true,
    'middleware'  => ['web', 'auth'],
],
```

Visit:
- `GET /pdf-studio/preview/{template}?format=html` — HTML preview
- `GET /pdf-studio/preview/{template}?format=pdf` — PDF preview

---

## Tailwind CSS

Point PDF Studio at your Tailwind binary and config:

```php
// config/pdf-studio.php
'tailwind' => [
    'binary' => env('TAILWIND_BINARY', base_path('node_modules/.bin/tailwindcss')),
    'config' => base_path('tailwind.config.js'),
],
```

Compiled CSS is cached automatically. Clear the cache:

```bash
php artisan pdf-studio:cache-clear
```

## Fonts & Assets

Register local fonts once and PDF Studio will embed them as generated `@font-face` CSS during rendering:

```php
'fonts' => [
    'inter' => [
        'family' => 'Inter',
        'sources' => [
            resource_path('fonts/Inter-Regular.ttf'),
        ],
        'weight' => '400',
        'style' => 'normal',
    ],
],
```

Asset policy can inline local assets and optionally block remote ones up front:

```php
'assets' => [
    'inline_local' => true,
    'allow_remote' => false,
    'allowed_hosts' => [
        'assets.example.com',
        'cdn.example.com',
    ],
],
```

This helps avoid renderer-specific failures around local file paths, missing images, font files, or remote asset fetches. The resolver covers:

- `<img src="...">`
- `<link rel="stylesheet" href="...">`
- CSS `url(...)` references inside linked stylesheets
- CSS `url(...)` references inside inline `<style>` blocks

---

## Template Versioning

> **Requires:** migrations (`php artisan vendor:publish --tag=pdf-studio-migrations && php artisan migrate`).

Save a snapshot of a template definition at any point:

```php
use PdfStudio\Laravel\Contracts\TemplateVersionServiceContract;

$versioning = app(TemplateVersionServiceContract::class);

// Save current version
$version = $versioning->create(
    definition:  $registry->get('invoice'),
    author:      'Jane Smith',
    changeNotes: 'Updated payment section layout',
);

// List version history
$versions = $versioning->list('invoice');
// Returns Collection<TemplateVersion> ordered newest first

// Restore a previous version
$definition = $versioning->restore('invoice', versionNumber: 3);

// Diff two versions
$changes = $versioning->diff('invoice', fromVersion: 2, toVersion: 3);
// Returns array of changed field names
```

---

## Workspaces & Access Control

> **Requires:** migrations (`php artisan vendor:publish --tag=pdf-studio-migrations && php artisan migrate`).

```php
use PdfStudio\Laravel\Models\Workspace;
use PdfStudio\Laravel\Models\WorkspaceMember;
use PdfStudio\Laravel\Contracts\AccessControlContract;

// Create a workspace
$workspace = Workspace::create(['name' => 'Acme Corp', 'slug' => 'acme']);

// Add members with roles: owner | admin | member | viewer
WorkspaceMember::create([
    'workspace_id' => $workspace->id,
    'user_id'      => $user->id,
    'role'         => 'admin',
]);

// Check access in code
$access = app(AccessControlContract::class);
$access->canAccess($user->id, $workspace->id);  // true/false
$access->canManage($user->id, $workspace->id);  // true for owner/admin

// Protect routes with middleware
Route::middleware('pdf-studio.workspace')->group(function () {
    // Route parameter must be named {workspace} (slug)
    Route::get('/workspaces/{workspace}/...', ...);
});
```

Scope projects to a workspace:

```php
use PdfStudio\Laravel\Models\Project;

$project = Project::create([
    'workspace_id' => $workspace->id,
    'name'         => 'Q4 Reports',
    'slug'         => 'q4-reports',
]);
```

---

## Visual Builder

> **Requires:** `pdf-studio.preview.enabled = true`

The visual builder lets you define document layouts as a JSON schema of typed blocks, preview them as HTML, and export them to Blade templates.

### Block Schema

```php
use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Builder\Schema\TextBlock;
use PdfStudio\Laravel\Builder\Schema\TableBlock;
use PdfStudio\Laravel\Builder\Schema\DataBinding;
use PdfStudio\Laravel\Builder\Schema\StyleTokens;

$schema = new DocumentSchema(
    blocks: [
        new TextBlock(content: 'Invoice', tag: 'h1', classes: 'text-2xl font-bold'),
        new TableBlock(
            headers: ['Item', 'Qty', 'Price'],
            rowBinding: new DataBinding(variable: 'items', path: 'items'),
            cellBindings: ['name', 'quantity', 'price'],
        ),
    ],
    styleTokens: new StyleTokens(
        primaryColor: '#1a1a1a',
        fontFamily: 'Inter, sans-serif',
    ),
);

// Serialize to JSON (store in DB, send to frontend)
$json = $schema->toJson();

// Restore from JSON
$schema = DocumentSchema::fromJson($json);
```

### Compile to HTML

```php
use PdfStudio\Laravel\Builder\Compiler\SchemaToHtmlCompiler;

$html = app(SchemaToHtmlCompiler::class)->compile($schema);
// Returns full HTML document ready to pass to PdfBuilder
```

### Export to Blade

```php
use PdfStudio\Laravel\Builder\Exporter\BladeExporter;

$blade = app(BladeExporter::class)->export($schema);
// Returns a Blade template string with @foreach loops for tables
file_put_contents(resource_path('views/pdf/invoice.blade.php'), $blade);
```

### Live Preview API

```
POST /pdf-studio/builder/preview
Content-Type: application/json

{
    "schema": { ...DocumentSchema JSON... },
    "format": "html"   // or "pdf"
}
```

---

## SaaS: Hosted Rendering API

> **Requires:** `PDF_STUDIO_SAAS=true` in `.env` and migrations.

Enable in `.env`:

```env
PDF_STUDIO_SAAS=true
```

### Issue an API Key

```php
use PdfStudio\Laravel\Models\Workspace;
use PdfStudio\Laravel\Models\ApiKey;

$workspace = Workspace::create(['name' => 'Acme Corp', 'slug' => 'acme']);

$generated = ApiKey::generate(); // ['key' => '...64 chars...', 'prefix' => '...8 chars...']

ApiKey::create([
    'workspace_id' => $workspace->id,
    'name'         => 'Production Key',
    'key'          => hash('sha256', $generated['key']), // store hash only
    'prefix'       => $generated['prefix'],
    'expires_at'   => now()->addYear(),                  // optional
]);

// Give $generated['key'] to your customer — it cannot be retrieved again
```

Revoke a key:

```php
$apiKey->revoke();
```

### Render Endpoints

All endpoints require:
```
Authorization: Bearer <raw_api_key>
```

**Sync — immediate PDF response:**

```bash
curl -X POST https://yourapp.com/api/pdf-studio/render \
  -H "Authorization: Bearer sk_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<h1>Invoice #42</h1>",
    "filename": "invoice-42.pdf"
  }'
# → application/pdf download
```

With a Blade view and options:

```json
{
    "view": "pdf.invoice",
    "data": {"invoice": {"id": 42, "total": 1200}},
    "options": {"format": "A4", "landscape": false},
    "driver": "chromium"
}
```

**Async — queue a render job:**

```bash
curl -X POST https://yourapp.com/api/pdf-studio/render/async \
  -H "Authorization: Bearer sk_abc123..." \
  -d '{
    "view": "pdf.report",
    "data": {"month": "January"},
    "output_path": "reports/jan.pdf",
    "output_disk": "s3"
  }'
# → 202 {"id": "uuid-...", "status": "pending"}
```

**Poll job status:**

```bash
curl https://yourapp.com/api/pdf-studio/render/{uuid} \
  -H "Authorization: Bearer sk_abc123..."
# → {"id": "...", "status": "completed", "bytes": 14200, "render_time_ms": 312.5}
```

Status values: `pending` | `completed` | `failed`

---

## SaaS: Usage Metering

Record usage events with idempotency (safe to call multiple times for the same job):

```php
use PdfStudio\Laravel\Contracts\UsageMeterContract;

$meter = app(UsageMeterContract::class);

$meter->recordRender(
    workspaceId:  $workspace->id,
    jobId:        $job->id,          // idempotency key — won't double-count
    bytes:        $result->bytes,
    renderTimeMs: $result->renderTimeMs,
);
```

Each `recordRender` call dispatches a `BillableEvent`. Hook into it to integrate your billing provider:

```php
// In AppServiceProvider::boot()
use PdfStudio\Laravel\Events\BillableEvent;

Event::listen(BillableEvent::class, function (BillableEvent $event) {
    // $event->workspaceId
    // $event->eventType  (e.g. 'render')
    // $event->quantity   (always 1 per render)
    // $event->metadata   (['bytes' => ..., 'render_time_ms' => ...])

    Stripe::meterEvent('pdf_render', [
        'stripe_customer_id' => Workspace::find($event->workspaceId)->stripe_id,
        'value' => $event->quantity,
    ]);
});
```

Query raw usage:

```php
$records = $meter->getUsage($workspace->id, now()->startOfMonth(), now()->endOfMonth());

$summary = $meter->getSummary($workspace->id, now()->startOfMonth(), now()->endOfMonth());
// ['render' => 1432]
```

---

## SaaS: Analytics

Query render stats for a workspace over a date range:

```php
use PdfStudio\Laravel\Contracts\AnalyticsServiceContract;

$analytics = app(AnalyticsServiceContract::class);

$stats = $analytics->getStats(
    workspaceId: $workspace->id,
    from:        now()->startOfMonth(),
    to:          now()->endOfMonth(),
);

// [
//   'total'              => 1432,
//   'completed'          => 1418,
//   'failed'             => 14,
//   'avg_render_time_ms' => 287.3,
//   'total_bytes'        => 48392104,
// ]
```

---

## PDF Merging

Merge multiple PDFs into a single document. Requires `setasign/fpdi`.

```php
use PdfStudio\Laravel\Facades\Pdf;

// Merge file paths
$result = Pdf::merge([
    storage_path('pdf/cover.pdf'),
    storage_path('pdf/report.pdf'),
    storage_path('pdf/appendix.pdf'),
]);

// Merge PdfResult objects
$page1 = Pdf::html('<h1>Page 1</h1>')->render();
$page2 = Pdf::html('<h1>Page 2</h1>')->render();
$result = Pdf::merge([$page1, $page2]);

// Merge with Storage paths and page ranges
$result = Pdf::merge([
    ['path' => 'documents/report.pdf', 'disk' => 's3', 'pages' => '1-3,5'],
    storage_path('pdf/appendix.pdf'),
]);

$result->download('merged.pdf');
```

---

## PDF Post-Processing

Operate on existing PDF bytes after rendering or on PDFs produced outside PDF Studio:

```php
$isPdf = Pdf::isPdf($pdfBytes);

$summary = Pdf::inspectPdf($pdfBytes);

Pdf::assertPdf($pdfBytes, 'uploaded report');

$totalPages = Pdf::pageCount($pdfBytes);

$chunks = Pdf::chunk($pdfBytes, 25);

$parts = Pdf::split($pdfBytes, ['1-2', '3-5']);

$flattened = Pdf::flattenPdf($pdfBytes);

$embedded = Pdf::embedFiles($pdfBytes, [[
    'path' => storage_path('app/reports/source.csv'),
    'name' => 'source.csv',
    'mime' => 'text/csv',
]]);

$isStoredPdf = Pdf::isPdfFile(storage_path('app/reports/annual.pdf'));

$storedSummary = Pdf::inspectPdfFile(storage_path('app/reports/annual.pdf'));

Pdf::assertPdfFile(storage_path('app/reports/annual.pdf'), 'stored annual report');

$totalPages = Pdf::pageCountFile(storage_path('app/reports/annual.pdf'));

$plannedRanges = Pdf::chunkRangesFile(storage_path('app/reports/annual.pdf'), 25);

$chunkPlan = Pdf::chunkPlanFile(storage_path('app/reports/annual.pdf'), 25);

$fileChunks = Pdf::chunkFile(storage_path('app/reports/annual.pdf'), 25);
```

`isPdf()` and `isPdfFile()` provide a cheap preflight check before queueing or manipulating uploaded content. `inspectPdf()` and `inspectPdfFile()` provide a combined summary with validity and page-count information when available. `assertPdf()` and `assertPdfFile()` provide a fail-fast validation path when invalid input should stop the workflow immediately. `pageCount()` and `pageCountFile()` return integers. `chunkRanges()` / `chunkRangesFile()` return plain page-range strings. `chunkPlan()` / `chunkPlanFile()` return structured planning metadata per chunk. `split()`, `chunk()`, and `chunkFile()` execute the staged split into `PdfResult` outputs. `flattenPdf()` / `flattenPdfFile()` and `embedFiles()` / `embedFilesIntoFile()` return a single `PdfResult`.

---

## Watermarking

Add text or image watermarks to rendered PDFs. Requires `setasign/fpdi`.

```php
// Text watermark
Pdf::html('<h1>Invoice</h1>')
    ->watermark('DRAFT', opacity: 0.3, fontSize: 72, position: 'center')
    ->download('invoice-draft.pdf');

// Image watermark
Pdf::view('report')
    ->watermarkImage(storage_path('images/logo.png'), opacity: 0.2, position: 'bottom-right')
    ->download('report.pdf');

// Watermark an existing PDF
$result = Pdf::watermarkPdf(file_get_contents('existing.pdf'))
    ->text('CONFIDENTIAL')
    ->opacity(0.5)
    ->rotation(-30)
    ->apply();
```

---

## Password Protection

Protect PDFs with user/owner passwords. Requires `mikehaertl/php-pdftk`.

```php
// Set both passwords
Pdf::html('<h1>Secret Report</h1>')
    ->protect(userPassword: 'user123', ownerPassword: 'admin456')
    ->download('protected.pdf');

// Owner password with restricted permissions
Pdf::view('contract')
    ->protect(
        ownerPassword: 'admin',
        permissions: ['Printing', 'CopyContents'],
    )
    ->save('contracts/signed.pdf');
```

---

## AcroForm Fill

Fill PDF form fields programmatically. Requires `mikehaertl/php-pdftk`.

```php
// Fill form fields
$result = Pdf::acroform(storage_path('forms/application.pdf'))
    ->fill([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'date' => '2024-01-15',
    ])
    ->flatten()
    ->output();

$result->download('application-filled.pdf');

// List available form fields
$fields = Pdf::acroform(storage_path('forms/application.pdf'))->fields();
// ['name', 'email', 'date', 'signature']
```

---

## Livewire / Filament

Download PDFs from Livewire components without Livewire intercepting the response:

```php
// In a Livewire component action
public function downloadInvoice()
{
    return Pdf::view('invoices.show')
        ->data(['invoice' => $this->invoice])
        ->livewireDownload('invoice.pdf');
}
```

Get base64 content for embedding:

```php
$result = Pdf::html('<h1>Report</h1>')->render();
$base64 = $result->toBase64(); // or $result->base64()
```

---

## Render Caching

Cache rendered PDFs to avoid re-rendering identical content:

```php
// Cache for 1 hour (3600 seconds)
$result = Pdf::html('<h1>Report</h1>')->cache(3600)->render();

// Second call returns cached result instantly (renderTimeMs = 0)
$result2 = Pdf::html('<h1>Report</h1>')->cache(3600)->render();

// Bypass cache for a specific render
$fresh = Pdf::html('<h1>Report</h1>')->cache(3600)->noCache()->render();
```

Configure defaults in config:

```php
// config/pdf-studio.php
'render_cache' => [
    'enabled' => true,
    'store'   => null, // uses default cache store
    'ttl'     => 3600,
],
```

Clear render cache:

```bash
php artisan pdf-studio:cache-clear --render
```

---

## Auto-Height Paper

Automatically size the paper height to fit content (no page breaks):

```php
// Auto-fit content height
Pdf::html('<h1>Long receipt...</h1>')
    ->contentFit()
    ->download('receipt.pdf');

// With maximum height cap (in pixels)
Pdf::view('receipt')
    ->contentFit(maxHeight: 3000)
    ->download('receipt.pdf');

// Alias
Pdf::html($html)->autoHeight()->render();
```

Supported by all drivers (Chromium, dompdf, wkhtmltopdf).

---

## Header/Footer Per-Page Control

Control header and footer visibility on specific pages (Chromium and wkhtmltopdf):

```php
// Hide header on the first page (e.g., cover page)
Pdf::view('report')
    ->headerExceptFirst()
    ->download('report.pdf');

// Hide footer on the last page
Pdf::view('report')
    ->footerExceptLast()
    ->download('report.pdf');

// Show header only on specific pages
Pdf::view('report')
    ->headerOnPages([2, 3, 4])
    ->download('report.pdf');

// Exclude header/footer from specific pages
Pdf::view('report')
    ->headerExcludePages([1, 5])
    ->footerExcludePages([1])
    ->download('report.pdf');
```

---

## Diagnostics

Run a health check on your PDF Studio installation:

```bash
php artisan pdf-studio:doctor
```

Checks:

- PHP version and memory limit
- DOM/XML extension
- temporary directory writability
- current default driver
- Node.js
- Cloudflare credential presence
- Gotenberg endpoint configuration and reachability when selected
- WeasyPrint availability
- dompdf, wkhtmltopdf, pdftk, FPDI, and Tailwind binary
- configured custom font paths
- asset policy summary
- a fake render pass

---

## Configuration Reference

```php
// config/pdf-studio.php

return [
    'default_driver' => env('PDF_STUDIO_DRIVER', 'chromium'),

    'tailwind' => [
        'binary' => env('TAILWIND_BINARY'),
        'config' => null,
    ],

    'preview' => [
        'enabled'              => env('PDF_STUDIO_PREVIEW', false),
        'middleware'           => ['web', 'auth'],
        'environment_gate'     => true,
        'allowed_environments' => ['local', 'staging', 'testing'],
    ],

    'logging' => [
        'enabled' => env('PDF_STUDIO_LOGGING', false),
        'channel' => null,
    ],

    'pro' => [
        'enabled'    => env('PDF_STUDIO_PRO', false),
        'versioning' => ['enabled' => true, 'max_versions' => 50],
        'workspaces' => [
            'enabled' => true,
            'roles'   => ['owner', 'admin', 'member', 'viewer'],
        ],
    ],

    'saas' => [
        'enabled'  => env('PDF_STUDIO_SAAS', false),
        'api'      => [
            'prefix'     => 'api/pdf-studio',
            'middleware' => ['api'],
            'rate_limit' => 60,
        ],
        'metering' => ['enabled' => true],
    ],
];
```

---

## Testing

```bash
composer test        # run all tests
composer analyse     # PHPStan level 6
composer lint        # Laravel Pint
```

### PdfFake (Testing Assertions)

Use `Pdf::fake()` in tests for fluent assertions without real PDF rendering:

```php
use PdfStudio\Laravel\Facades\Pdf;

it('generates an invoice PDF', function () {
    $fake = Pdf::fake();

    // ... trigger the code that generates a PDF ...

    $fake->assertRendered();
    $fake->assertRenderedView('invoices.show');
    $fake->assertRenderedCount(1);
    $fake->assertDownloaded('invoice.pdf');
    $fake->assertSavedTo('invoices/inv-001.pdf', 's3');
    $fake->assertDriverWas('chromium');
    $fake->assertContains('Invoice');
    $fake->assertMerged();
    $fake->assertMergedCount(2);
    $fake->assertWatermarked();
    $fake->assertProtected();
    $fake->assertNothingRendered();
});
```

Or use the `fake` driver directly:

```php
config(['pdf-studio.default_driver' => 'fake']);
```

---

## Documentation

The README covers the full API surface. For deeper guidance see the **[User Guide](docs/user-guide.html)**, which includes:

- Page layout examples (paper sizes, margins, headers/footers, multi-column)
- Framework integration guides — Livewire, Vue 3, React, Node.js, vanilla JavaScript
- Troubleshooting — Tailwind class issues, image paths, custom fonts, page breaks, driver differences

## License

MIT — see [LICENSE](LICENSE).
