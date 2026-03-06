# PDF Studio for Laravel

Design, preview, and generate PDFs using HTML and TailwindCSS in Laravel.

## Installation

```bash
composer require pdfstudio/laravel
```

Publish the config:

```bash
php artisan vendor:publish --tag=pdf-studio-config
```

## Quick Start

```php
use PdfStudio\Laravel\Facades\Pdf;

// Generate and download a PDF
Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->download('invoice.pdf');

// Save to storage
Pdf::view('reports.quarterly')
    ->data(['report' => $report])
    ->driver('chromium')
    ->format('A4')
    ->landscape()
    ->save('reports/q1.pdf', 's3');

// Render from inline HTML
$result = Pdf::html('<h1>Hello World</h1>')->render();
```

## Supported Drivers

| Driver | Package | Node Required | CSS Fidelity |
|---|---|---|---|
| Chromium (default) | `spatie/browsershot` | Yes | Full |
| wkhtmltopdf | System binary | No | Good |
| dompdf | `dompdf/dompdf` | No | Limited |

Install your preferred driver:

```bash
# Chromium (recommended)
composer require spatie/browsershot

# dompdf (no external binaries)
composer require dompdf/dompdf
```

## Template Registry

Register named templates with default options:

```php
// In config/pdf-studio.php
'templates' => [
    'invoice' => [
        'view' => 'pdf.invoice',
        'default_options' => ['format' => 'A4'],
        'data_provider' => App\Pdf\InvoiceDataProvider::class,
    ],
],

// Usage
Pdf::template('invoice')->data(['id' => 123])->download('invoice.pdf');
```

List registered templates:

```bash
php artisan pdf-studio:templates
```

## Queue Integration

Render PDFs asynchronously:

```php
use PdfStudio\Laravel\Jobs\RenderPdfJob;

// Single job
RenderPdfJob::dispatch(
    view: 'invoices.show',
    data: ['invoice' => $invoice],
    outputPath: 'invoices/inv-001.pdf',
    disk: 's3',
);

// Batch rendering
Pdf::batch([
    ['view' => 'invoices.show', 'data' => $inv1, 'outputPath' => 'inv-1.pdf'],
    ['view' => 'invoices.show', 'data' => $inv2, 'outputPath' => 'inv-2.pdf'],
], driver: 'dompdf', disk: 's3');
```

## Blade Directives

```blade
{{-- Force a page break --}}
@pageBreak

{{-- Page break before content --}}
@pageBreakBefore

{{-- Prevent content from splitting across pages --}}
@avoidBreak
    <div>Keep this together</div>
@endAvoidBreak
```

## Preview Routes

Enable browser-based template preview (disabled in production by default):

```php
// config/pdf-studio.php
'preview' => [
    'enabled' => true,
    'middleware' => ['web', 'auth'],
],
```

Visit `/pdf-studio/preview/{template}?format=html` or `?format=pdf`.

## Tailwind CSS

Configure the Tailwind CSS compilation pipeline:

```php
'tailwind' => [
    'binary' => '/path/to/tailwindcss',
    'config' => base_path('tailwind.config.js'),
],
```

## Configuration

See the [config file](config/pdf-studio.php) for all options.

## Testing

```bash
composer test
composer analyse
composer lint
```

## License

MIT
