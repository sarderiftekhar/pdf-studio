<?php

use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Drivers\DriverManager;
use PdfStudio\Laravel\Drivers\FakeDriver;
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
