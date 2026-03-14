<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\CloudflareDriver;
use PdfStudio\Laravel\Drivers\DriverManager;
use PdfStudio\Laravel\Drivers\FakeDriver;
use PdfStudio\Laravel\Drivers\GotenbergDriver;
use PdfStudio\Laravel\Drivers\WeasyPrintDriver;
use PdfStudio\Laravel\Exceptions\DriverException;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('resolves the default driver', function () {
    $manager = app(DriverManager::class);

    $driver = $manager->driver();

    expect($driver)->toBeInstanceOf(RendererContract::class);
});

it('resolves a named driver', function () {
    $manager = app(DriverManager::class);

    $driver = $manager->driver('fake');

    expect($driver)->toBeInstanceOf(FakeDriver::class);
});

it('resolves gotenberg driver when configured', function () {
    $this->app['config']->set('pdf-studio.drivers.gotenberg.url', 'http://gotenberg.test');

    $manager = app(DriverManager::class);

    $driver = $manager->driver('gotenberg');

    expect($driver)->toBeInstanceOf(GotenbergDriver::class);
});

it('resolves cloudflare driver when configured', function () {
    $this->app['config']->set('pdf-studio.drivers.cloudflare.account_id', 'acc_123');
    $this->app['config']->set('pdf-studio.drivers.cloudflare.api_token', 'token_123');

    $manager = app(DriverManager::class);

    $driver = $manager->driver('cloudflare');

    expect($driver)->toBeInstanceOf(CloudflareDriver::class);
});

it('resolves weasyprint driver when configured', function () {
    $this->app['config']->set('pdf-studio.drivers.weasyprint.binary', 'weasyprint');

    $manager = app(DriverManager::class);

    $driver = $manager->driver('weasyprint');

    expect($driver)->toBeInstanceOf(WeasyPrintDriver::class);
});

it('throws on unknown driver', function () {
    $manager = app(DriverManager::class);

    $manager->driver('nonexistent');
})->throws(DriverException::class, 'Driver [nonexistent] is not configured');

it('caches driver instances', function () {
    $manager = app(DriverManager::class);

    $driver1 = $manager->driver('fake');
    $driver2 = $manager->driver('fake');

    expect($driver1)->toBe($driver2);
});
