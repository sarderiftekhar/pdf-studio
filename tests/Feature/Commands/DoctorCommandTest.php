<?php

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('runs doctor command successfully', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('PDF Studio Diagnostics')
        ->assertExitCode(0)
        ->run();
})->skip(!file_exists('/usr/local/bin/wkhtmltopdf') && PHP_OS_FAMILY !== 'Linux', 'Optional binaries not available');

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

it('checks pdftk availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('pdftk');
});

it('checks fpdi availability', function () {
    $this->artisan('pdf-studio:doctor')
        ->expectsOutputToContain('fpdi');
});
