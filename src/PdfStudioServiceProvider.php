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

        $this->app->singleton(Debug\DebugRecorder::class, function ($app) {
            return new Debug\DebugRecorder($app);
        });

        $this->app->singleton(Templates\TemplateRegistry::class, function () {
            return new Templates\TemplateRegistry;
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pdf-studio');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pdf-studio.php' => config_path('pdf-studio.php'),
            ], 'pdf-studio-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pdf-studio'),
            ], 'pdf-studio-views');

            $this->commands([
                Commands\CacheClearCommand::class,
                Commands\TemplateListCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'pdf-studio-migrations');
        }

        $this->registerConfigTemplates();
        $this->registerStarterTemplates();
        $this->registerPreviewRoutes();
        $this->registerBladeDirectives();
        $this->registerEventListeners();
    }

    protected function registerConfigTemplates(): void
    {
        /** @var array<string, array<string, mixed>> $templates */
        $templates = $this->app['config']->get('pdf-studio.templates', []);

        $registry = $this->app->make(Templates\TemplateRegistry::class);

        foreach ($templates as $name => $config) {
            $registry->register(new DTOs\TemplateDefinition(
                name: $name,
                view: $config['view'] ?? '',
                description: $config['description'] ?? null,
                defaultOptions: $config['default_options'] ?? [],
                dataProvider: $config['data_provider'] ?? null,
            ));
        }
    }

    protected function registerStarterTemplates(): void
    {
        if (!$this->app['config']->get('pdf-studio.starter_templates', false)) {
            return;
        }

        $registry = $this->app->make(Templates\TemplateRegistry::class);

        foreach (Templates\StarterTemplates::definitions() as $definition) {
            if (!$registry->has($definition->name)) {
                $registry->register($definition);
            }
        }
    }

    protected function registerPreviewRoutes(): void
    {
        if (!$this->app['config']->get('pdf-studio.preview.enabled', false)) {
            return;
        }

        // Environment gate: block in production by default
        if ($this->app['config']->get('pdf-studio.preview.environment_gate', true)) {
            $allowed = $this->app['config']->get('pdf-studio.preview.allowed_environments', ['local', 'staging', 'testing']);
            if (!in_array($this->app->environment(), $allowed, true)) {
                return;
            }
        }

        $prefix = $this->app['config']->get('pdf-studio.preview.prefix', 'pdf-studio/preview');
        $middleware = $this->app['config']->get('pdf-studio.preview.middleware', ['web', 'auth']);

        $this->app['router']->group([
            'prefix' => $prefix,
            'middleware' => $middleware,
        ], function ($router) {
            $router->get('{template}', [Preview\PreviewController::class, 'show'])
                ->where('template', '.*')
                ->name('pdf-studio.preview');
        });
    }

    protected function registerBladeDirectives(): void
    {
        \Illuminate\Support\Facades\Blade::directive('pageBreak', function () {
            return '<div style="page-break-after: always; break-after: page;"></div>';
        });

        \Illuminate\Support\Facades\Blade::directive('pageBreakBefore', function () {
            return '<div style="page-break-before: always; break-before: page;"></div>';
        });

        \Illuminate\Support\Facades\Blade::directive('avoidBreak', function () {
            return '<div style="page-break-inside: avoid; break-inside: avoid;">';
        });

        \Illuminate\Support\Facades\Blade::directive('endAvoidBreak', function () {
            return '</div>';
        });
    }

    protected function registerEventListeners(): void
    {
        if (!$this->app['config']->get('pdf-studio.logging.enabled', false)) {
            return;
        }

        $logger = $this->app->make(Listeners\RenderLogger::class);

        $this->app['events']->listen(Events\RenderStarting::class, [$logger, 'handleStarting']);
        $this->app['events']->listen(Events\RenderCompleted::class, [$logger, 'handleCompleted']);
        $this->app['events']->listen(Events\RenderFailed::class, [$logger, 'handleFailed']);
    }
}
