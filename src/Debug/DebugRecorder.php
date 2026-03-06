<?php

namespace PdfStudio\Laravel\Debug;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\DTOs\RenderContext;

class DebugRecorder
{
    protected bool $enabled;

    public function __construct(Application $app)
    {
        $this->enabled = (bool) $app['config']->get('pdf-studio.debug', false);
    }

    public function record(RenderContext $context, string $driver, float $renderTimeMs): void
    {
        if (!$this->enabled) {
            return;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $dir = "pdf-studio/debug/{$timestamp}";

        if ($context->compiledHtml !== null) {
            Storage::disk('local')->put("{$dir}/compiled.html", $context->compiledHtml);
        }

        if ($context->compiledCss !== null) {
            Storage::disk('local')->put("{$dir}/compiled.css", $context->compiledCss);
        }

        if ($context->styledHtml !== null) {
            Storage::disk('local')->put("{$dir}/styled.html", $context->styledHtml);
        }

        $metadata = [
            'driver' => $driver,
            'render_time_ms' => $renderTimeMs,
            'view' => $context->viewName,
            'format' => $context->options->format ?? 'A4',
            'landscape' => $context->options->landscape ?? false,
            'timestamp' => $timestamp,
            'has_css' => $context->compiledCss !== null,
        ];

        Storage::disk('local')->put(
            "{$dir}/metadata.json",
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
    }
}
