# PDF Studio for Laravel

Design, preview, and generate PDFs using HTML and TailwindCSS in Laravel.

## Installation

```bash
composer require pdfstudio/laravel
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
Pdf::html('<h1>Hello World</h1>')
    ->render();
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=pdf-studio-config
```

## Supported Drivers

| Driver | Binary Required | CSS Fidelity |
|---|---|---|
| Chromium (default) | Node.js + Puppeteer | Full |
| wkhtmltopdf | wkhtmltopdf binary | Good |
| dompdf | None | Limited |

## Testing

```bash
composer test
```

## License

MIT
