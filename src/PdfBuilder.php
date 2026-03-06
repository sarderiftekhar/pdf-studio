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
        $this->context->viewName = $view;
        $this->context->rawHtml = null;

        return $this;
    }

    public function html(string $html): static
    {
        $this->context->rawHtml = $html;
        $this->context->viewName = null;

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

        $pipeline = $this->app->make(RenderPipeline::class);
        $context = $pipeline->run($this->context, $driverName);

        $renderTimeMs = (microtime(true) - $startTime) * 1000;

        return new PdfResult(
            content: $context->pdfContent ?? '',
            driver: $driverName,
            renderTimeMs: $renderTimeMs,
        );
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
}
