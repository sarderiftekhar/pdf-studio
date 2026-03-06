# PDF Studio for Laravel

Design, preview, and generate PDFs using HTML and TailwindCSS in Laravel.

[![Tests](https://img.shields.io/badge/tests-261%20passing-brightgreen)](tests)
[![PHPStan](https://img.shields.io/badge/phpstan-level%206-blue)](phpstan.neon)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

> **Free and open source.** All features — including template versioning, workspaces, the visual builder, and the hosted rendering API — are available under the MIT license with no key, subscription, or license fee required.

---

## Table of Contents

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
- [Configuration Reference](#configuration-reference)
- [Testing](#testing)

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

| Driver | Package | Node | CSS Fidelity |
|--------|---------|------|--------------|
| `chromium` (default) | `spatie/browsershot` | Yes | Full |
| `wkhtmltopdf` | System binary | No | Good |
| `dompdf` | `dompdf/dompdf` | No | Limited |
| `fake` | Built-in | No | Testing only |

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

Use the `fake` driver in tests to avoid real PDF rendering:

```php
// In your TestCase or test file
config(['pdf-studio.default_driver' => 'fake']);

// The fake driver returns a minimal valid PDF bytes response
// All assertions on ->render(), ->download(), ->save() work normally
```

---

## License

MIT — see [LICENSE](LICENSE).
