<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use PdfStudio\Laravel\DTOs\RenderContext;

class BootstrapInjector
{
    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        $html = $context->compiledHtml ?? '';

        if ($html === '') {
            return $next($context);
        }

        $cssPath = __DIR__.'/../../resources/css/bootstrap.min.css';

        if (!file_exists($cssPath)) {
            return $next($context);
        }

        $context->compiledCss = file_get_contents($cssPath);

        return $next($context);
    }
}
