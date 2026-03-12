# PDF Studio Driver Guide

This guide explains when to choose each PDF Studio driver, what each driver supports well, and where users commonly run into trouble.

## Quick Recommendation

Use this default decision tree:

- Choose `chromium` when you want the highest CSS fidelity and you control the runtime.
- Choose `cloudflare` when you want managed Chromium rendering without running Chrome yourself.
- Choose `gotenberg` when you want remote rendering but prefer self-hosting.
- Choose `weasyprint` when you need print-native output, attachments, or PDF variants and your documents are CSS-print oriented.
- Choose `dompdf` only for lightweight cases where zero extra services matter more than CSS accuracy.
- Choose `wkhtmltopdf` only for legacy compatibility or existing infrastructure.

## Driver Matrix

| Driver | Runtime Model | CSS Fidelity | JS Support | Best Strength | Main Tradeoff |
|--------|---------------|--------------|------------|---------------|---------------|
| `chromium` | Local Chrome + Browsershot | Excellent | Yes | Best general-purpose HTML fidelity | Requires Node and browser runtime |
| `cloudflare` | Managed remote Chromium | Excellent | Yes | No local browser management | External service dependency |
| `gotenberg` | Self-hosted remote Chromium | Excellent | Yes | Good for queue workers and isolated render infra | Requires separate service operation |
| `weasyprint` | Local binary | Good for print CSS | No browser JS | Great print-native workflows and PDF variants | Not a browser engine |
| `dompdf` | PHP package | Limited | No | Easiest install footprint | Weak support for modern layouts |
| `wkhtmltopdf` | Local binary | Moderate | Limited | Stable for some legacy stacks | Old rendering engine and dated web support |
| `fake` | Built-in | N/A | N/A | Testing | Not a real renderer |

## Capability Summary

### Chromium

Use when:

- your templates rely on modern CSS
- you need JavaScript execution
- you need readiness controls such as `waitForSelector`, `waitForNetworkIdle`, or `waitDelay`
- you want tagged PDFs or outline support

Good at:

- Tailwind-heavy layouts
- chart-heavy dashboards after readiness hooks
- complex responsive HTML adapted for print

Typical problems:

- fonts differ between local, CI, and Docker environments
- images or CSS load in the browser but fail in production due to file path or network differences
- charts render too early if you do not wait for the page to settle

Recommended mitigations:

- configure fonts explicitly under `pdf-studio.fonts`
- use `waitForFonts()`
- use `waitForSelector()` or `waitForNetworkIdle()`
- use `waitDelay()` for chart animation or async hydration edge cases

### Cloudflare

Use when:

- you want Chromium fidelity but do not want to manage Chrome or Node on your servers
- your infrastructure already uses Cloudflare and external rendering is acceptable

Good at:

- removing browser-runtime operational burden
- scaling rendering away from app nodes
- keeping app containers smaller

Typical problems:

- external service credentials and account configuration
- remote service latency
- expectations that every Chromium option is available immediately

Recommended mitigations:

- verify credentials with `php artisan pdf-studio:doctor`
- use it for queued or background workloads first
- keep templates deterministic and avoid depending on local-only assets

### Gotenberg

Use when:

- you want remote rendering but need to keep it inside your own infrastructure
- you want a clean separation between app and rendering runtime

Good at:

- self-hosted remote render workers
- queue-heavy workloads
- predictable render environments once the service is standardized

Typical problems:

- service reachability and operations
- mismatches between app asset paths and what the renderer can fetch
- queue workers succeeding locally but failing against a remote renderer due to blocked assets

Recommended mitigations:

- run `php artisan pdf-studio:doctor` with `gotenberg` as the default driver
- use inlined local assets when possible
- restrict remote assets with `assets.allowed_hosts`

### WeasyPrint

Use when:

- your documents are truly print-oriented rather than browser-app-oriented
- you want PDF variant workflows such as PDF/A or PDF/UA targets
- you need attachments and print semantics more than browser JavaScript

Good at:

- print CSS
- document-oriented templates
- metadata and attachment workflows

Typical problems:

- users expect browser JS features to work
- templates designed for Chromium may not map cleanly to print-native rendering

Recommended mitigations:

- keep WeasyPrint templates simple and print-first
- avoid JS-dependent charts or hydration
- use it selectively for archival or compliance-oriented outputs

### dompdf

Use when:

- you need the smallest dependency surface
- your documents are simple and do not rely on modern CSS or JS

Typical problems:

- flexbox and advanced layout limitations
- images and remote assets failing due to path or config constraints
- inconsistent page breaking for complex documents

Recommended mitigations:

- keep templates conservative
- inline assets when possible
- avoid using dompdf for high-fidelity browser-like layouts

### wkhtmltopdf

Use when:

- you already operate it successfully and do not want to migrate immediately

Typical problems:

- old rendering engine behavior
- poor support for modern web features
- surprising differences from current Chromium rendering

Recommended mitigations:

- treat it as legacy compatibility
- prefer Chromium, Cloudflare, or Gotenberg for new work

## Common Failure Patterns

These are the most frequent classes of PDF generation failures across HTML-to-PDF tools.

### Fonts and Unicode

Symptoms:

- text renders with fallback fonts
- Arabic or CJK output breaks
- PDFs differ across local, Docker, and production

Mitigations:

- register fonts under `pdf-studio.fonts`
- keep font files in versioned application paths
- verify configured font file paths with `php artisan pdf-studio:doctor`
- prefer explicit render readiness with `waitForFonts()` on Chromium paths

### Assets and Images

Symptoms:

- images appear in browser preview but disappear in PDF
- remote images work locally but fail in production
- CSS files referenced with `<link>` do not resolve on remote renderers

Mitigations:

- enable local asset inlining
- set `assets.allow_remote=false` unless remote fetches are required
- if remote assets are needed, use `assets.allowed_hosts`
- remember that PDF Studio now resolves CSS `url(...)` assets in linked stylesheets and inline `<style>` blocks, not just `<img>` tags
- prefer stable absolute URLs or inline assets for remote renderers

### Async Content and Charts

Symptoms:

- charts or tables render half-complete
- PDFs capture loading states or skeletons
- output is inconsistent between runs

Mitigations:

- use `waitForSelector()` for a deterministic ready marker
- use `waitForNetworkIdle()` when the page has a clear network-settle point
- use `waitDelay()` when animations or chart libraries need extra time
- split unstable sections into separate renders and combine them with `compose()`

### Large Documents

Symptoms:

- timeouts
- memory pressure
- random failures on long reports

Mitigations:

- use `compose()` to render sections separately
- use remote renderers for isolation if app nodes are resource-constrained
- prefer simpler layouts for very long reports
- use `isPdf()` or `isPdfFile()` before downstream manipulation when files come from uploads or external systems
- use `assertPdf()` or `assertPdfFile()` when invalid input should fail the job immediately instead of branching
- use `pageCount()` first when you need to plan downstream batching or export stages
- use `chunk()` for fixed-size page grouping before storage, transport, or review workflows
- split, flatten, or embed files into existing PDFs when downstream workflows need smaller stages or bundled source material

## Practical Recipes

### Browser-like high fidelity

```php
Pdf::view('reports.full')
    ->driver('chromium')
    ->waitForFonts()
    ->waitForNetworkIdle()
    ->waitForSelector('#ready', ['visible' => true])
    ->download('report.pdf');
```

### Remote rendering with self-hosted infrastructure

```php
Pdf::view('reports.full')
    ->driver('gotenberg')
    ->waitForNetworkIdle()
    ->waitDelay(500)
    ->download('report.pdf');
```

### Large-document staging

```php
$pdf = Pdf::view('reports.annual')
    ->driver('gotenberg')
    ->render();

$looksValid = Pdf::isPdf($pdf->content());

$summary = Pdf::inspectPdf($pdf->content());
// ['valid' => true, 'page_count' => 42, 'byte_size' => 182304]

Pdf::assertPdf($pdf->content(), 'rendered annual report');

$pageCount = Pdf::pageCount($pdf->content());

$ranges = Pdf::chunkRanges($pdf->content(), 50);

$plan = Pdf::chunkPlan($pdf->content(), 50);

$chunks = Pdf::chunk($pdf->content(), 50);

$storedLooksValid = Pdf::isPdfFile(storage_path('app/reports/annual.pdf'));

$storedSummary = Pdf::inspectPdfFile(storage_path('app/reports/annual.pdf'));
// ['valid' => true, 'page_count' => 42, 'byte_size' => 182304]

Pdf::assertPdfFile(storage_path('app/reports/annual.pdf'), 'stored annual report');

$storedRanges = Pdf::chunkRangesFile(storage_path('app/reports/annual.pdf'), 50);

$storedPlan = Pdf::chunkPlanFile(storage_path('app/reports/annual.pdf'), 50);
```

### Archival or print-native workflow

```php
Pdf::view('reports.archive')
    ->driver('weasyprint')
    ->metadata(['title' => 'Archive Copy'])
    ->pdfVariant('pdf/a-3u')
    ->download('archive.pdf');
```

### Large report composition

```php
Pdf::compose([
    ['view' => 'reports.cover'],
    ['view' => 'reports.summary'],
    ['view' => 'reports.detail'],
], driver: 'cloudflare');
```

## Supporting Commands

Use the doctor command regularly:

```bash
php artisan pdf-studio:doctor
```

Use it to verify:

- binary availability
- temp storage access
- font file configuration
- default driver selection
- Cloudflare or Gotenberg setup
- asset policy configuration

## Bottom Line

Choose the driver based on workload, not habit.

- Use Chromium-family drivers for browser-like templates.
- Use WeasyPrint for print-native and archival workflows.
- Use dompdf only for simple, dependency-light documents.
- Treat wkhtmltopdf as legacy support, not the default direction.
