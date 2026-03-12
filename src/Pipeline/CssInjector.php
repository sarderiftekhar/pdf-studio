<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use PdfStudio\Laravel\Fonts\FontCssGenerator;
use PdfStudio\Laravel\DTOs\RenderContext;

class CssInjector
{
    public function __construct(
        protected ?FontCssGenerator $fontCssGenerator = null,
    ) {}

    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        $html = $context->compiledHtml ?? '';
        $fontCss = $this->fontCssGenerator?->generate() ?? '';
        $combinedCss = trim($fontCss.($fontCss !== '' && $context->compiledCss ? "\n" : '').($context->compiledCss ?? ''));

        if ($combinedCss === '') {
            $context->styledHtml = $html;

            return $next($context);
        }

        $styleTag = '<style>'.$combinedCss.'</style>';

        if (stripos($html, '</head>') !== false) {
            $context->styledHtml = str_ireplace('</head>', $styleTag.'</head>', $html);
        } else {
            $context->styledHtml = "<!DOCTYPE html>\n<html>\n<head>{$styleTag}</head>\n<body>{$html}</body>\n</html>";
        }

        return $next($context);
    }
}
