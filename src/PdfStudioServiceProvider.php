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

        $this->app->singleton(Cache\CssCache::class, function ($app) {
            return new Cache\CssCache($app);
        });

        $this->app->bind(Pipeline\RenderPipeline::class);
        $this->app->bind(Pipeline\BladeCompiler::class);
        $this->app->bind(Pipeline\PdfRenderer::class);

        $this->app->bind(Pipeline\TailwindCompiler::class, function ($app) {
            return new Pipeline\TailwindCompiler(
                cache: $app->make(Cache\CssCache::class),
                binary: $app['config']->get('pdf-studio.tailwind.binary'),
                configPath: $app['config']->get('pdf-studio.tailwind.config'),
                timeout: 60,
            );
        });

        $this->app->bind(Contracts\CssCompilerContract::class, Pipeline\TailwindCompiler::class);
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
