<?php

use Illuminate\Support\ServiceProvider;
use PdfStudio\Laravel\PdfStudioServiceProvider;

function packageMigrationsPath(): string
{
    return realpath(__DIR__.'/../../database/migrations');
}

function registeredMigrationPaths(): array
{
    return array_map(
        fn (string $path) => realpath($path) ?: $path,
        app('migrator')->paths(),
    );
}

function rebootProvider(): void
{
    (new PdfStudioServiceProvider(app()))->boot();
}

it('does not load package migrations by default', function () {
    expect(registeredMigrationPaths())->not->toContain(packageMigrationsPath());
});

it('does not load package migrations when pro and saas are disabled', function () {
    config(['pdf-studio.pro.enabled' => false, 'pdf-studio.saas.enabled' => false]);

    rebootProvider();

    expect(registeredMigrationPaths())->not->toContain(packageMigrationsPath());
});

it('loads package migrations when saas is enabled', function () {
    config(['pdf-studio.saas.enabled' => true]);

    rebootProvider();

    expect(registeredMigrationPaths())->toContain(packageMigrationsPath());
});

it('loads package migrations when pro is enabled', function () {
    config(['pdf-studio.pro.enabled' => true]);

    rebootProvider();

    expect(registeredMigrationPaths())->toContain(packageMigrationsPath());
});

it('keeps the migrations publish tag regardless of feature flags', function () {
    $published = array_map(
        fn (string $path) => realpath($path) ?: $path,
        array_keys(ServiceProvider::pathsToPublish(PdfStudioServiceProvider::class, 'pdf-studio-migrations')),
    );

    expect($published)->toContain(packageMigrationsPath());
});
