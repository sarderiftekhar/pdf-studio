<?php

namespace PdfStudio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\DTOs\WatermarkOptions;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;
use PdfStudio\Laravel\Pipeline\RenderPipeline;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfBuilder
{
    protected RenderContext $context;

    protected ?string $driver = null;

    protected ?int $cacheTtl = null;

    protected bool $noCache = false;

    public function __construct(
        protected Application $app,
    ) {
        $this->context = new RenderContext;
    }

    public function view(string $view): static
    {
        $this->context = new RenderContext;
        $this->driver = null;
        $this->cacheTtl = null;
        $this->noCache = false;
        $this->context->viewName = $view;

        return $this;
    }

    public function html(string $html): static
    {
        $this->context = new RenderContext;
        $this->driver = null;
        $this->cacheTtl = null;
        $this->noCache = false;
        $this->context->rawHtml = $html;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function data(array $data): static
    {
        $this->context->data = $data;

        return $this;
    }

    public function driver(string $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function bootstrap(): static
    {
        $this->context->cssFramework = 'bootstrap';

        return $this;
    }

    public function tailwind(): static
    {
        $this->context->cssFramework = 'tailwind';

        return $this;
    }

    public function format(string $format): static
    {
        $this->context->options->format = $format;

        return $this;
    }

    public function landscape(bool $landscape = true): static
    {
        $this->context->options->landscape = $landscape;

        return $this;
    }

    public function margins(
        ?int $top = null,
        ?int $right = null,
        ?int $bottom = null,
        ?int $left = null,
    ): static {
        if ($top !== null) {
            $this->context->options->margins['top'] = $top;
        }
        if ($right !== null) {
            $this->context->options->margins['right'] = $right;
        }
        if ($bottom !== null) {
            $this->context->options->margins['bottom'] = $bottom;
        }
        if ($left !== null) {
            $this->context->options->margins['left'] = $left;
        }

        return $this;
    }

    public function template(string $name): static
    {
        $registry = $this->app->make(\PdfStudio\Laravel\Templates\TemplateRegistry::class);
        $definition = $registry->get($name);

        $this->view($definition->view);

        // Apply default options
        if (isset($definition->defaultOptions['format'])) {
            $this->format($definition->defaultOptions['format']);
        }
        if (isset($definition->defaultOptions['landscape'])) {
            $this->landscape((bool) $definition->defaultOptions['landscape']);
        }
        if (isset($definition->defaultOptions['margins'])) {
            $margins = $definition->defaultOptions['margins'];
            $this->margins(
                $margins['top'] ?? null,
                $margins['right'] ?? null,
                $margins['bottom'] ?? null,
                $margins['left'] ?? null,
            );
        }

        // Resolve data provider if configured
        if ($definition->dataProvider !== null) {
            /** @var \PdfStudio\Laravel\Contracts\PreviewDataProviderContract $provider */
            $provider = $this->app->make($definition->dataProvider);
            $this->data($provider->data());
        }

        return $this;
    }

    // ---- Render Caching (Feature 10) ----

    public function cache(?int $ttl = null): static
    {
        $this->cacheTtl = $ttl ?? (int) config('pdf-studio.render_cache.ttl', 3600);

        return $this;
    }

    public function noCache(): static
    {
        $this->noCache = true;

        return $this;
    }

    // ---- Livewire/Filament (Feature 1) ----

    public function livewireDownload(string $filename): StreamedResponse
    {
        $result = $this->render();

        return new StreamedResponse(function () use ($result) {
            echo $result->content();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) $result->bytes,
        ]);
    }

    // ---- Watermarking (Feature 5) ----

    public function watermark(
        string $text,
        float $opacity = 0.3,
        int $rotation = -45,
        string $position = 'center',
        int $fontSize = 48,
        string $color = '#999999',
    ): static {
        $this->context->options->watermark = new WatermarkOptions(
            text: $text,
            opacity: $opacity,
            rotation: $rotation,
            position: $position,
            fontSize: $fontSize,
            color: $color,
        );

        return $this;
    }

    public function watermarkImage(
        string $imagePath,
        float $opacity = 0.3,
        string $position = 'center',
    ): static {
        $this->context->options->watermark = new WatermarkOptions(
            imagePath: $imagePath,
            opacity: $opacity,
            position: $position,
        );

        return $this;
    }

    /**
     * Create a standalone watermark builder for existing PDF bytes.
     */
    public function watermarkPdf(string $pdfBytes): Manipulation\WatermarkBuilder
    {
        return new Manipulation\WatermarkBuilder($this->app, $pdfBytes);
    }

    // ---- Password Protection (Feature 6) ----

    /**
     * @param  array<string>  $permissions
     */
    public function protect(
        ?string $userPassword = null,
        ?string $ownerPassword = null,
        array $permissions = [],
    ): static {
        $this->context->options->userPassword = $userPassword;
        $this->context->options->ownerPassword = $ownerPassword;
        $this->context->options->permissions = $permissions;

        return $this;
    }

    // ---- Auto-Height (Feature 8) ----

    public function contentFit(int $maxHeight = 5000): static
    {
        $this->context->options->autoHeight = true;
        $this->context->options->maxHeight = $maxHeight;

        return $this;
    }

    public function autoHeight(int $maxHeight = 5000): static
    {
        return $this->contentFit($maxHeight);
    }

    // ---- Header/Footer Per-Page Control (Feature 9) ----

    public function headerExceptFirst(): static
    {
        $this->context->options->headerExceptFirst = true;

        return $this;
    }

    public function footerExceptLast(): static
    {
        $this->context->options->footerExceptLast = true;

        return $this;
    }

    /**
     * @param  array<int>  $pages
     */
    public function headerOnPages(array $pages): static
    {
        $this->context->options->headerOnPages = $pages;

        return $this;
    }

    /**
     * @param  array<int>  $pages
     */
    public function headerExcludePages(array $pages): static
    {
        $this->context->options->headerExcludePages = $pages;

        return $this;
    }

    /**
     * @param  array<int>  $pages
     */
    public function footerExcludePages(array $pages): static
    {
        $this->context->options->footerExcludePages = $pages;

        return $this;
    }

    // ---- PDF Merging (Feature 3) ----

    /**
     * @param  array<int, string|PdfResult|array{path: string, disk?: string, pages?: string}>  $sources
     */
    public function merge(array $sources): PdfResult
    {
        $merger = $this->app->make(\PdfStudio\Laravel\Contracts\MergerContract::class);

        return $merger->merge($sources);
    }

    // ---- AcroForm Fill (Feature 4) ----

    public function acroform(string $pdfPath): Manipulation\AcroFormBuilder
    {
        return new Manipulation\AcroFormBuilder($this->app, $pdfPath);
    }

    // ---- Thumbnail (Feature 7) ----

    public function thumbnail(int $width = 300, string $format = 'png', int $quality = 85, int $page = 1): Thumbnail\ThumbnailResult
    {
        $pdfResult = $this->render();

        $generator = $this->app->make(Thumbnail\ThumbnailGenerator::class);

        return $generator->generate($pdfResult->content(), $page, $width, $format, $quality);
    }

    // ---- Table of Contents (Feature 13) ----

    public function withTableOfContents(int $depth = 6, string $title = 'Table of Contents', string $mode = 'auto'): static
    {
        $this->context->options->tocOptions = new DTOs\TocOptions(
            depth: $depth,
            title: $title,
            mode: $mode,
        );

        return $this;
    }

    public function withBookmarks(): static
    {
        if ($this->context->options->tocOptions === null) {
            $this->context->options->tocOptions = new DTOs\TocOptions;
        }

        $this->context->options->tocOptions->bookmarks = true;

        return $this;
    }

    // ---- Core Methods ----

    public function getContext(): RenderContext
    {
        return $this->context;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function getResolvedDriverName(): string
    {
        return $this->driver ?? $this->app['config']->get('pdf-studio.default_driver', 'chromium');
    }

    public function render(): PdfResult
    {
        $startTime = microtime(true);
        $driverName = $this->getResolvedDriverName();
        $html = $this->context->rawHtml ?? $this->context->viewName ?? '';

        // Check render cache
        $renderCache = $this->app->make(Cache\RenderCache::class);
        $cacheKey = null;

        if ($this->cacheTtl !== null && !$this->noCache) {
            $cacheKey = $renderCache->key(
                $this->context->viewName ?? $this->context->rawHtml ?? '',
                $this->context->data,
                (array) $this->context->options,
                $driverName,
            );

            $cached = $renderCache->get($cacheKey);
            if ($cached !== null) {
                return new PdfResult(content: $cached, driver: $driverName, renderTimeMs: 0);
            }
        }

        event(new \PdfStudio\Laravel\Events\RenderStarting(
            html: $html,
            driver: $driverName,
            viewName: $this->context->viewName,
        ));

        try {
            $pipeline = $this->app->make(RenderPipeline::class);
            $context = $pipeline->run($this->context, $driverName);

            // Two-pass TOC rendering
            if ($this->context->options->tocOptions !== null) {
                $tocExtractor = $this->app->make(TableOfContents\TocExtractor::class);
                $tocRenderer = $this->app->make(TableOfContents\TocRenderer::class);
                $tocOptions = $this->context->options->tocOptions;

                // Extract headings from the compiled HTML
                $compiledHtml = $context->styledHtml ?? $context->compiledHtml ?? '';
                $entries = $tocExtractor->extract($compiledHtml, $tocOptions);

                if (count($entries) > 0) {
                    // Inject anchors into the HTML
                    $anchoredHtml = $tocExtractor->injectAnchors($compiledHtml, $tocOptions);

                    // Render TOC HTML (page numbers are 0 for now)
                    $tocHtml = $tocRenderer->render($entries, $tocOptions);

                    // Prepend TOC to document and re-render (skip Blade/CSS compilation)
                    $this->context->styledHtml = $tocHtml.$anchoredHtml;
                    $this->context->compiledHtml = $tocHtml.$anchoredHtml;
                    $context = $pipeline->runRenderOnly($this->context, $driverName);
                }
            }

            // Post-render: watermark
            if ($this->context->options->watermark !== null) {
                $watermarker = $this->app->make(\PdfStudio\Laravel\Contracts\WatermarkerContract::class);
                $context->pdfContent = $watermarker->apply(
                    $context->pdfContent ?? '',
                    $this->context->options->watermark,
                );
            }

            // Post-render: password protection
            if ($this->context->options->userPassword !== null || $this->context->options->ownerPassword !== null) {
                $protector = $this->app->make(\PdfStudio\Laravel\Contracts\ProtectorContract::class);
                $context->pdfContent = $protector->protect(
                    $context->pdfContent ?? '',
                    $this->context->options->userPassword,
                    $this->context->options->ownerPassword,
                    $this->context->options->permissions,
                );
            }

            $renderTimeMs = (microtime(true) - $startTime) * 1000;

            $result = new PdfResult(
                content: $context->pdfContent ?? '',
                driver: $driverName,
                renderTimeMs: $renderTimeMs,
            );

            // Store in render cache
            if ($cacheKey !== null) {
                $renderCache->put($cacheKey, $context->pdfContent ?? '', $this->cacheTtl);
            }

            event(new \PdfStudio\Laravel\Events\RenderCompleted(
                driver: $driverName,
                renderTimeMs: $renderTimeMs,
                bytes: $result->bytes,
            ));

            $debugRecorder = $this->app->make(\PdfStudio\Laravel\Debug\DebugRecorder::class);
            $debugRecorder->record($context, $driverName, $renderTimeMs);

            return $result;
        } catch (\Throwable $e) {
            event(new \PdfStudio\Laravel\Events\RenderFailed(
                driver: $driverName,
                exception: $e,
            ));

            throw $e;
        }
    }

    public function download(string $filename): \Illuminate\Http\Response
    {
        return $this->render()->download($filename);
    }

    public function stream(string $filename): StreamedResponse
    {
        return $this->render()->stream($filename);
    }

    public function save(string $path, ?string $disk = null): StorageResult
    {
        return $this->render()->save($path, $disk);
    }

    /**
     * Dispatch multiple PDF render jobs to the queue.
     *
     * @param  array<array{view: string, data?: array<string, mixed>, outputPath: string, driver?: string, disk?: string, options?: array<string, mixed>}>  $items
     */
    public function batch(array $items, ?string $driver = null, ?string $disk = null): void
    {
        foreach ($items as $item) {
            Jobs\RenderPdfJob::dispatch(
                view: $item['view'],
                data: $item['data'] ?? [],
                outputPath: $item['outputPath'],
                disk: $item['disk'] ?? $disk,
                driver: $item['driver'] ?? $driver,
                options: $item['options'] ?? [],
            );
        }
    }
}
