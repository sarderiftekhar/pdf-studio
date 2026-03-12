<?php

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('runs doctor command successfully', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('PDF Studio Diagnostics');
})->skip(fn () => (bool) getenv('CI'), 'Optional binaries not available in CI');

it('runs doctor command and outputs diagnostics', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('PDF Studio Diagnostics');
});

it('checks PHP version', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('PHP Version');
});

it('reports memory limit', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Memory Limit');
});

it('checks DOM extension availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('DOM extension');
});

it('reports temporary directory diagnostics', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Temporary directory writable');
});

it('reports the default driver', function () {
    config(['pdf-studio.default_driver' => 'weasyprint']);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Default driver: weasyprint');
});

it('checks dompdf availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('dompdf');
});

it('performs test render with fake driver', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Test render');
});

it('checks Node.js availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Node.js');
});

it('reports cloudflare browser rendering configuration when present', function () {
    config([
        'pdf-studio.drivers.cloudflare.account_id' => 'acc_123',
        'pdf-studio.drivers.cloudflare.api_token' => 'token_123',
    ]);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Cloudflare Browser Rendering configured');
});

it('checks pdftk availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('pdftk');
});

it('checks fpdi availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('fpdi');
});

it('reports asset policy settings', function () {
    config([
        'pdf-studio.assets.allow_remote' => false,
        'pdf-studio.assets.inline_local' => true,
    ]);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Asset policy: remote assets blocked; inline local assets enabled');
});

it('reports allowed remote asset hosts when configured', function () {
    config([
        'pdf-studio.assets.allow_remote' => true,
        'pdf-studio.assets.inline_local' => true,
        'pdf-studio.assets.allowed_hosts' => ['assets.example.com', 'cdn.example.com'],
    ]);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('allowed remote hosts: assets.example.com, cdn.example.com');
});

it('reports gotenberg reachability when it is the default driver', function () {
    config([
        'pdf-studio.default_driver' => 'gotenberg',
        'pdf-studio.drivers.gotenberg.url' => 'http://127.0.0.1:65534',
    ]);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Gotenberg endpoint reachable');
});

it('reports when no custom fonts are configured', function () {
    config(['pdf-studio.fonts' => []]);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('No custom fonts configured');
});

it('checks configured font file paths', function () {
    $fontFile = tempnam(sys_get_temp_dir(), 'pdfstudio_font_');
    file_put_contents($fontFile, 'font');

    config([
        'pdf-studio.fonts' => [
            'inter' => [
                'family' => 'Inter',
                'sources' => [$fontFile],
            ],
        ],
    ]);

    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('Font [inter] source');

    @unlink($fontFile);
});
