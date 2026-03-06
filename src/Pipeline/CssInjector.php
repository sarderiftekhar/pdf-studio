<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use PdfStudio\Laravel\DTOs\RenderContext;

class CssInjector
{
    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        $html = $context->compiledHtml ?? '';

        if ($context->compiledCss === null || $context->compiledCss === '') {
            $context->styledHtml = $html;

            return $next($context);
        }

        $styleTag = '<style>'.$context->compiledCss.'</style>';

        if (stripos($html, '</head>') !== false) {
            $context->styledHtml = str_ireplace('</head>', $styleTag.'</head>', $html);
        } else {
            $context->styledHtml = "<!DOCTYPE html>\n<html>\n<head>{$styleTag}</head>\n<body>{$html}</body>\n</html>";
        }

        return $next($context);
    }
}
