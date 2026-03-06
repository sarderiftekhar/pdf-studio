<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Output\PdfResult;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'dompdf');
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', false);
});

it('generates a PDF with Tailwind CSS through the full pipeline', function () {
    $binary = findTailwindBinaryForIntegration();

    if ($binary === null) {
        $this->markTestSkipped('Tailwind CSS binary not found');
    }

    $this->app['config']->set('pdf-studio.tailwind.binary', $binary);

    $html = <<<'HTML'
    <html>
    <body>
        <div class="text-red-500 font-bold text-2xl p-4">
            Hello Tailwind PDF
        </div>
        <p class="mt-4 text-gray-600">
            This PDF was styled with Tailwind CSS.
        </p>
    </body>
    </html>
    HTML;

    $result = Pdf::html($html)->render();

    expect($result)->toBeInstanceOf(PdfResult::class)
        ->and($result->driver)->toBe('dompdf')
        ->and($result->content())->toStartWith('%PDF')
        ->and($result->bytes)->toBeGreaterThan(100);
});

it('generates a PDF without tailwind when binary not configured', function () {
    $this->app['config']->set('pdf-studio.tailwind.binary', null);

    $result = Pdf::html('<div class="text-red-500">No Tailwind</div>')->render();

    expect($result->content())->toStartWith('%PDF');
});

function findTailwindBinaryForIntegration(): ?string
{
    $paths = [
        base_path('node_modules/.bin/tailwindcss'),
        '/usr/local/bin/tailwindcss',
        trim((string) shell_exec('which tailwindcss 2>/dev/null')),
    ];

    foreach ($paths as $path) {
        if (!empty($path) && is_executable($path)) {
            return $path;
        }
    }

    return null;
}
