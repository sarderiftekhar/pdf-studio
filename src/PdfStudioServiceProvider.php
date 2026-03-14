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

        $this->app->bind(PdfBuilder::class, function ($app) {
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

        $this->app->singleton(Fonts\FontRegistry::class, function ($app) {
            return new Fonts\FontRegistry($app);
        });

        $this->app->singleton(Fonts\FontCssGenerator::class, function ($app) {
            return new Fonts\FontCssGenerator($app->make(Fonts\FontRegistry::class));
        });

        $this->app->bind(Pipeline\RenderPipeline::class);
        $this->app->bind(Pipeline\BladeCompiler::class);
        $this->app->bind(Pipeline\PdfRenderer::class);
        $this->app->bind(Pipeline\CssInjector::class);
        $this->app->bind(Pipeline\AssetResolver::class);

        $this->app->bind(Pipeline\BootstrapInjector::class);

        $this->app->bind(Pipeline\TailwindCompiler::class, function ($app) {
            return new Pipeline\TailwindCompiler(
                cache: $app->make(Cache\CssCache::class),
                binary: $app['config']->get('pdf-studio.tailwind.binary'),
                configPath: $app['config']->get('pdf-studio.tailwind.config'),
                timeout: 60,
            );
        });

        $this->app->bind(Contracts\CssCompilerContract::class, Pipeline\TailwindCompiler::class);

        $this->app->bind(Contracts\AccessControlContract::class, Services\AccessControl::class);

        $this->app->bind(
            Contracts\TemplateVersionServiceContract::class,
            Services\TemplateVersionService::class
        );

        $this->app->bind(Contracts\UsageMeterContract::class, Services\UsageMeter::class);

        $this->app->bind(Contracts\AnalyticsServiceContract::class, Services\AnalyticsService::class);

        $this->app->bind(Testing\PdfFake::class, function ($app) {
            return new Testing\PdfFake($app);
        });

        $this->app->singleton(Cache\RenderCache::class, function ($app) {
            return new Cache\RenderCache($app);
        });

        $this->app->bind(Contracts\MergerContract::class, Manipulation\PdfMerger::class);
        $this->app->bind(Contracts\WatermarkerContract::class, Manipulation\PdfWatermarker::class);
        $this->app->bind(Contracts\AcroFormContract::class, Manipulation\AcroFormFiller::class);
        $this->app->bind(Contracts\ProtectorContract::class, Manipulation\PdfProtector::class);
        $this->app->bind(Manipulation\PdfFlattener::class);
        $this->app->bind(Manipulation\PdfPageCounter::class);
        $this->app->bind(Manipulation\PdfValidator::class);
        $this->app->bind(Manipulation\PdfInspector::class);
        $this->app->bind(Manipulation\PdfMetadataReader::class);
        $this->app->bind(Manipulation\PdfPageEditor::class);
        $this->app->bind(Manipulation\PdfPageRotator::class);
        $this->app->bind(Manipulation\PdfSplitter::class);
        $this->app->bind(Manipulation\PdfChunker::class);
        $this->app->bind(Manipulation\PdfEmbedder::class);

        $this->app->bind(Thumbnail\ThumbnailGenerator::class, function ($app) {
            return new Thumbnail\ThumbnailGenerator(
                strategy: $app['config']->get('pdf-studio.thumbnail.strategy', 'auto'),
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pdf-studio');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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
                Commands\DoctorCommand::class,
                Commands\InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'pdf-studio-migrations');
        }

        $this->registerConfigTemplates();
        $this->registerStarterTemplates();
        $this->registerPreviewRoutes();
        $this->registerBuilderPreviewRoutes();
        $this->registerApiRoutes();
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

    protected function registerApiRoutes(): void
    {
        if (!$this->app['config']->get('pdf-studio.saas.enabled', false)) {
            return;
        }

        $prefix = $this->app['config']->get('pdf-studio.saas.api.prefix', 'api/pdf-studio');
        $middleware = $this->app['config']->get('pdf-studio.saas.api.middleware', ['api']);

        $this->app['router']->group([
            'prefix' => $prefix,
            'middleware' => array_merge($middleware, [Http\Middleware\ApiKeyAuth::class]),
        ], function ($router) {
            $router->post('render', [Http\Controllers\Api\RenderController::class, 'sync'])
                ->name('pdf-studio.api.render');
            $router->post('render/async', [Http\Controllers\Api\RenderController::class, 'async'])
                ->name('pdf-studio.api.render.async');
            $router->get('render/{jobId}', [Http\Controllers\Api\RenderController::class, 'status'])
                ->name('pdf-studio.api.render.status');
        });
    }

    protected function registerBuilderPreviewRoutes(): void
    {
        if (!$this->app['config']->get('pdf-studio.preview.enabled', false)) {
            return;
        }

        if ($this->app['config']->get('pdf-studio.preview.environment_gate', true)) {
            $allowed = $this->app['config']->get('pdf-studio.preview.allowed_environments', ['local', 'staging', 'testing']);
            if (!in_array($this->app->environment(), $allowed, true)) {
                return;
            }
        }

        $middleware = $this->app['config']->get('pdf-studio.preview.middleware', ['web', 'auth']);

        $this->app['router']->group([
            'prefix' => 'pdf-studio/builder',
            'middleware' => $middleware,
        ], function ($router) {
            $router->post('preview', [Builder\Preview\BuilderPreviewController::class, 'preview'])
                ->name('pdf-studio.builder.preview');
        });
    }

    protected function registerBladeDirectives(): void
    {
        \Illuminate\Support\Facades\Blade::directive('pageBreak', function () {
            return '<?php echo \'<div style="page-break-after: always; break-after: page;"></div>\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('pageBreakBefore', function () {
            return '<?php echo \'<div style="page-break-before: always; break-before: page;"></div>\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('avoidBreak', function () {
            return '<?php echo \'<div style="page-break-inside: avoid; break-inside: avoid;">\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('endAvoidBreak', function () {
            return '<?php echo \'</div>\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('pageNumber', function ($expression) {
            return "<?php echo app(\PdfStudio\Laravel\Layout\PageNumberGenerator::class)->footer({$expression}); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('showIf', function ($expression) {
            return "<?php if(!({$expression})): echo '<div style=\"display: none;\">'; else: echo '<div>'; endif; ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endShowIf', function () {
            return '<?php echo \'</div>\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('keepTogether', function () {
            return '<?php echo \'<div style="page-break-inside: avoid; break-inside: avoid;">\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('endKeepTogether', function () {
            return '<?php echo \'</div>\'; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('barcode', function ($expression) {
            return "<?php echo app(\PdfStudio\Laravel\Barcode\BarcodeGenerator::class)->generate({$expression}); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('qrcode', function ($expression) {
            return "<?php echo app(\PdfStudio\Laravel\Barcode\QrCodeGenerator::class)->generate({$expression}); ?>";
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
