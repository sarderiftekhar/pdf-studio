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
        $assetResolver = $this->app->make(AssetResolver::class);
        $cssStep = $this->resolveCssStep($context);
        $cssInjector = $this->app->make(CssInjector::class);
        $pdfRenderer = $this->app->make(PdfRenderer::class);

        if ($driverName !== null) {
            $pdfRenderer->setDriver($driverName);
        }

        return $bladeCompiler->handle($context, function ($context) use ($assetResolver, $cssStep, $cssInjector, $pdfRenderer) {
            return $assetResolver->handle($context, function ($context) use ($cssStep, $cssInjector, $pdfRenderer) {
                return $cssStep->handle($context, function ($context) use ($cssInjector, $pdfRenderer) {
                    return $cssInjector->handle($context, function ($context) use ($pdfRenderer) {
                        return $pdfRenderer->handle($context, fn ($ctx) => $ctx);
                    });
                });
            });
        });
    }

    /**
     * Run only CSS injection and PDF rendering (skip Blade and CSS compilation).
     * Used for the TOC second pass where HTML is already compiled.
     */
    public function runRenderOnly(RenderContext $context, ?string $driverName = null): RenderContext
    {
        $cssInjector = new CssInjector;
        $pdfRenderer = $this->app->make(PdfRenderer::class);

        if ($driverName !== null) {
            $pdfRenderer->setDriver($driverName);
        }

        return $cssInjector->handle($context, function ($context) use ($pdfRenderer) {
            return $pdfRenderer->handle($context, fn ($ctx) => $ctx);
        });
    }

    protected function resolveCssStep(RenderContext $context): TailwindCompiler|BootstrapInjector|NullCssStep
    {
        $framework = $context->cssFramework
            ?? $this->app['config']->get('pdf-studio.css_framework', 'tailwind');

        return match ($framework) {
            'bootstrap' => $this->app->make(BootstrapInjector::class),
            'none' => new NullCssStep,
            default => $this->app->make(TailwindCompiler::class),
        };
    }
}
