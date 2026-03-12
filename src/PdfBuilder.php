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

    public function pageRanges(string $pageRanges): static
    {
        $this->context->options->pageRanges = $pageRanges;

        return $this;
    }

    public function preferCssPageSize(bool $prefer = true): static
    {
        $this->context->options->preferCssPageSize = $prefer;

        return $this;
    }

    public function scale(float $scale): static
    {
        $this->context->options->scale = $scale;

        return $this;
    }

    public function waitForFonts(bool $wait = true): static
    {
        $this->context->options->waitForFonts = $wait;

        return $this;
    }

    public function waitUntil(string $event): static
    {
        $this->context->options->waitUntil = $event;

        return $this;
    }

    public function waitForNetworkIdle(bool $strict = true): static
    {
        $this->context->options->waitUntil = $strict ? 'networkidle0' : 'networkidle2';

        return $this;
    }

    public function waitDelay(int $milliseconds): static
    {
        $this->context->options->waitDelayMs = $milliseconds;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function waitForSelector(string $selector, array $options = []): static
    {
        $this->context->options->waitForSelector = $selector;
        $this->context->options->waitForSelectorOptions = $options;

        return $this;
    }

    public function waitForFunction(string $function, int $timeout = 0): static
    {
        $this->context->options->waitForFunction = $function;
        $this->context->options->waitForFunctionTimeout = $timeout;

        return $this;
    }

    public function taggedPdf(bool $tagged = true): static
    {
        $this->context->options->taggedPdf = $tagged;

        return $this;
    }

    public function outline(bool $outline = true): static
    {
        $this->context->options->outline = $outline;

        return $this;
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function metadata(array $metadata): static
    {
        $this->context->options->metadata = $metadata;

        return $this;
    }

    public function pdfVariant(string $variant): static
    {
        $this->context->options->pdfVariant = $variant;

        return $this;
    }

    public function attachFile(string $path, ?string $name = null, ?string $mime = null): static
    {
        $this->context->options->attachments[] = array_filter([
            'name' => $name ?? basename($path),
            'path' => $path,
            'mime' => $mime,
        ], static fn ($value): bool => $value !== null);

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

    /**
     * Split an existing PDF into multiple PdfResult parts by page ranges.
     *
     * @param  array<int, string>  $ranges
     * @return array<int, PdfResult>
     */
    public function split(string $pdfContent, array $ranges): array
    {
        $splitter = $this->app->make(Manipulation\PdfSplitter::class);

        return $splitter->split($pdfContent, $ranges);
    }

    /**
     * @param  array<int, string>  $ranges
     * @return array<int, PdfResult>
     */
    public function splitFile(string $path, array $ranges): array
    {
        return $this->split($this->readPdfFile($path), $ranges);
    }

    public function flattenPdf(string $pdfContent): PdfResult
    {
        $flattener = $this->app->make(Manipulation\PdfFlattener::class);

        return $flattener->flatten($pdfContent);
    }

    public function flattenPdfFile(string $path): PdfResult
    {
        return $this->flattenPdf($this->readPdfFile($path));
    }

    public function pageCount(string $pdfContent): int
    {
        $counter = $this->app->make(Manipulation\PdfPageCounter::class);

        return $counter->count($pdfContent);
    }

    public function isPdf(string $pdfContent): bool
    {
        $validator = $this->app->make(Manipulation\PdfValidator::class);

        return $validator->isPdf($pdfContent);
    }

    public function isPdfFile(string $path): bool
    {
        return $this->isPdf($this->readPdfFile($path));
    }

    public function assertPdf(string $pdfContent, string $label = 'content'): void
    {
        $validator = $this->app->make(Manipulation\PdfValidator::class);
        $validator->assertPdf($pdfContent, $label);
    }

    public function assertPdfFile(string $path, string $label = 'file'): void
    {
        $this->assertPdf($this->readPdfFile($path), $label);
    }

    /**
     * @return array{valid: bool, page_count: int|null, byte_size: int}
     */
    public function inspectPdf(string $pdfContent): array
    {
        $inspector = $this->app->make(Manipulation\PdfInspector::class);

        return $inspector->inspect($pdfContent);
    }

    /**
     * @return array{valid: bool, page_count: int|null, byte_size: int}
     */
    public function inspectPdfFile(string $path): array
    {
        return $this->inspectPdf($this->readPdfFile($path));
    }

    public function pageCountFile(string $path): int
    {
        return $this->pageCount($this->readPdfFile($path));
    }

    /**
     * @return array<int, PdfResult>
     */
    public function chunk(string $pdfContent, int $pagesPerChunk): array
    {
        $chunker = $this->app->make(Manipulation\PdfChunker::class);

        return $chunker->chunk($pdfContent, $pagesPerChunk);
    }

    /**
     * @return array<int, PdfResult>
     */
    public function chunkFile(string $path, int $pagesPerChunk): array
    {
        return $this->chunk($this->readPdfFile($path), $pagesPerChunk);
    }

    /**
     * @return array<int, string>
     */
    public function chunkRanges(string $pdfContent, int $pagesPerChunk): array
    {
        $chunker = $this->app->make(Manipulation\PdfChunker::class);

        return $chunker->chunkRanges($pdfContent, $pagesPerChunk);
    }

    /**
     * @return array<int, array{index: int, start: int, end: int, pages: int, range: string}>
     */
    public function chunkPlan(string $pdfContent, int $pagesPerChunk): array
    {
        $chunker = $this->app->make(Manipulation\PdfChunker::class);

        return $chunker->chunkPlan($pdfContent, $pagesPerChunk);
    }

    /**
     * @return array<int, string>
     */
    public function chunkRangesFile(string $path, int $pagesPerChunk): array
    {
        return $this->chunkRanges($this->readPdfFile($path), $pagesPerChunk);
    }

    /**
     * @return array<int, array{index: int, start: int, end: int, pages: int, range: string}>
     */
    public function chunkPlanFile(string $path, int $pagesPerChunk): array
    {
        return $this->chunkPlan($this->readPdfFile($path), $pagesPerChunk);
    }

    /**
     * @param  array<int, array{path: string, name?: string|null, mime?: string|null}>  $files
     */
    public function embedFiles(string $pdfContent, array $files): PdfResult
    {
        $embedder = $this->app->make(Manipulation\PdfEmbedder::class);

        return $embedder->embed($pdfContent, $files);
    }

    /**
     * @param  array<int, array{path: string, name?: string|null, mime?: string|null}>  $files
     */
    public function embedFilesIntoFile(string $path, array $files): PdfResult
    {
        return $this->embedFiles($this->readPdfFile($path), $files);
    }

    /**
     * Render multiple sections independently and merge them into one PDF.
     *
     * @param  array<int, array{view?: string, html?: string, data?: array<string, mixed>, driver?: string, options?: array<string, mixed>}>  $documents
     */
    public function compose(array $documents, ?string $driver = null): PdfResult
    {
        $results = [];

        foreach ($documents as $document) {
            $builder = $this->app->make(self::class);

            if (isset($document['view'])) {
                $builder->view($document['view']);
            } elseif (isset($document['html'])) {
                $builder->html($document['html']);
            } else {
                throw new \InvalidArgumentException('Each composed document must define either [view] or [html].');
            }

            if (isset($document['data']) && is_array($document['data'])) {
                $builder->data($document['data']);
            }

            $builder->applyArrayOptions($document['options'] ?? []);

            if (isset($document['driver'])) {
                $builder->driver($document['driver']);
            } elseif ($driver !== null) {
                $builder->driver($driver);
            }

            $results[] = $builder->render();
        }

        return $this->merge($results);
    }

    protected function readPdfFile(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \PdfStudio\Laravel\Exceptions\RenderException("Cannot read file: {$path}");
        }

        return $content;
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

                    // Prepend TOC to document and re-render
                    $this->context->styledHtml = $tocHtml.$anchoredHtml;
                    $this->context->compiledHtml = $tocHtml.$anchoredHtml;
                    $context = $pipeline->run($this->context, $driverName);
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

    /**
     * @param  array<string, mixed>  $options
     */
    protected function applyArrayOptions(array $options): void
    {
        if (isset($options['format'])) {
            $this->format((string) $options['format']);
        }

        if (isset($options['landscape'])) {
            $this->landscape((bool) $options['landscape']);
        }

        if (isset($options['margins']) && is_array($options['margins'])) {
            $margins = $options['margins'];
            $this->margins(
                $margins['top'] ?? null,
                $margins['right'] ?? null,
                $margins['bottom'] ?? null,
                $margins['left'] ?? null,
            );
        }

        if (isset($options['pageRanges'])) {
            $this->pageRanges((string) $options['pageRanges']);
        }

        if (isset($options['preferCssPageSize'])) {
            $this->preferCssPageSize((bool) $options['preferCssPageSize']);
        }

        if (isset($options['scale'])) {
            $this->scale((float) $options['scale']);
        }

        if (isset($options['waitForFonts'])) {
            $this->waitForFonts((bool) $options['waitForFonts']);
        }

        if (isset($options['waitUntil'])) {
            $this->waitUntil((string) $options['waitUntil']);
        }

        if (isset($options['waitDelayMs'])) {
            $this->waitDelay((int) $options['waitDelayMs']);
        }

        if (isset($options['waitForSelector'])) {
            $this->waitForSelector(
                (string) $options['waitForSelector'],
                is_array($options['waitForSelectorOptions'] ?? null) ? $options['waitForSelectorOptions'] : [],
            );
        }

        if (isset($options['waitForFunction'])) {
            $this->waitForFunction(
                (string) $options['waitForFunction'],
                (int) ($options['waitForFunctionTimeout'] ?? 0),
            );
        }

        if (isset($options['taggedPdf'])) {
            $this->taggedPdf((bool) $options['taggedPdf']);
        }

        if (isset($options['outline'])) {
            $this->outline((bool) $options['outline']);
        }

        if (isset($options['metadata']) && is_array($options['metadata'])) {
            $this->metadata($options['metadata']);
        }

        if (isset($options['pdfVariant'])) {
            $this->pdfVariant((string) $options['pdfVariant']);
        }

        if (isset($options['attachments']) && is_array($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                if (!is_array($attachment) || !isset($attachment['path'])) {
                    continue;
                }

                $this->attachFile(
                    (string) $attachment['path'],
                    isset($attachment['name']) ? (string) $attachment['name'] : null,
                    isset($attachment['mime']) ? (string) $attachment['mime'] : null,
                );
            }
        }
    }
}
