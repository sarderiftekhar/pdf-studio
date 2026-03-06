<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Exceptions\RenderException;

class BladeCompiler
{
    public function __construct(
        protected ViewFactory $viewFactory,
    ) {}

    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        if ($context->viewName !== null) {
            $context->compiledHtml = $this->viewFactory
                ->make($context->viewName, $context->data)
                ->render();
        } elseif ($context->rawHtml !== null) {
            $context->compiledHtml = $context->rawHtml;
        } else {
            throw new RenderException('No view or HTML content provided.');
        }

        return $next($context);
    }
}
