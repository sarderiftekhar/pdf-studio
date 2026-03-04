<?php

namespace PdfStudio\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
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
            'Pdf' => \PdfStudio\Laravel\Facades\Pdf::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pdf-studio.default_driver', 'chromium');
    }
}
