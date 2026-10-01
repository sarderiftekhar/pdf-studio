<?php

namespace PdfStudio\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\PdfStudioServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PdfStudioServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Pdf' => Pdf::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pdf-studio.default_driver', 'chromium');

        // Testbench 8 (Laravel 10) defaults to MySQL; use the in-memory SQLite connection everywhere.
        $app['config']->set('database.default', 'testing');
    }

    /**
     * The provider only loads its migrations when Pro / SaaS is enabled,
     * so database-backed tests load them explicitly.
     */
    protected function defineDatabaseMigrations(): void
    {
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
