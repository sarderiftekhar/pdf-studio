<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use PdfStudio\Laravel\DTOs\RenderContext;

class NullCssStep
{
    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        return $next($context);
    }
}
