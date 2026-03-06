<?php

namespace PdfStudio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Output\PdfResult;
use PdfStudio\Laravel\Output\StorageResult;
use PdfStudio\Laravel\Pipeline\RenderPipeline;

class PdfBuilder
{
    protected RenderContext $context;

    protected ?string $driver = null;

    public function __construct(
        protected Application $app,
    ) {
        $this->context = new RenderContext;
    }

    public function view(string $view): static
    {
        $this->context = new RenderContext;
        $this->driver = null;
        $this->context->viewName = $view;

        return $this;
    }

    public function html(string $html): static
    {
        $this->context = new RenderContext;
        $this->driver = null;
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

        event(new \PdfStudio\Laravel\Events\RenderStarting(
            html: $html,
            driver: $driverName,
            viewName: $this->context->viewName,
        ));

        try {
            $pipeline = $this->app->make(RenderPipeline::class);
            $context = $pipeline->run($this->context, $driverName);
            $renderTimeMs = (microtime(true) - $startTime) * 1000;

            $result = new PdfResult(
                content: $context->pdfContent ?? '',
                driver: $driverName,
                renderTimeMs: $renderTimeMs,
            );

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

    public function stream(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
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
