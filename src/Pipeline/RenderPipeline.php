<?php

namespace PdfStudio\Laravel\Pipeline;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\DTOs\RenderContext;

class RenderPipeline
{
    public function __construct(
        protected Application $app,
    ) {}

    public function run(RenderContext $context, ?string $driverName = null): RenderContext
    {
        $bladeCompiler = $this->app->make(BladeCompiler::class);
        $cssInjector = new CssInjector;
        $pdfRenderer = $this->app->make(PdfRenderer::class);

        if ($driverName !== null) {
            $pdfRenderer->setDriver($driverName);
        }

        return $bladeCompiler->handle($context, function ($context) use ($cssInjector, $pdfRenderer) {
            return $cssInjector->handle($context, function ($context) use ($pdfRenderer) {
                return $pdfRenderer->handle($context, fn ($ctx) => $ctx);
            });
        });
    }
}
