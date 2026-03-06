<?php

namespace PdfStudio\Laravel;

use Illuminate\Support\ServiceProvider;

class PdfStudioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pdf-studio.php',
            'pdf-studio'
        );

        $this->app->singleton(PdfBuilder::class, function ($app) {
            return new PdfBuilder($app);
        });

        $this->app->singleton(Drivers\DriverManager::class, function ($app) {
            return new Drivers\DriverManager($app);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pdf-studio.php' => config_path('pdf-studio.php'),
            ], 'pdf-studio-config');
        }
    }
}
