<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use PdfStudio\Laravel\Cache\CssCache;
use PdfStudio\Laravel\Contracts\CssCompilerContract;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Exceptions\RenderException;
use Symfony\Component\Process\Process;

class TailwindCompiler implements CssCompilerContract
{
    public function __construct(
        protected CssCache $cache,
        protected ?string $binary = null,
        protected ?string $configPath = null,
        protected int $timeout = 60,
    ) {}

    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        $html = $context->compiledHtml ?? '';

        if ($this->binary === null || $html === '') {
            return $next($context);
        }

        $cacheKey = $this->cache->key($html);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            $context->compiledCss = $cached;

            return $next($context);
        }

        $css = $this->compile($html);
        $context->compiledCss = $css;

        $this->cache->put($cacheKey, $css);

        return $next($context);
    }

    public function compile(string $html): string
    {
        if ($this->binary === null) {
            return '';
        }

        $tempDir = sys_get_temp_dir();
        $contentFile = tempnam($tempDir, 'pdfstudio_tw_');
        $inputCssFile = tempnam($tempDir, 'pdfstudio_tw_css_');

        if ($contentFile === false || $inputCssFile === false) {
            throw new RenderException('Failed to create temporary file for Tailwind compilation.');
        }

        try {
            file_put_contents($contentFile, $html);

            // Tailwind v4 uses @source directive in CSS instead of --content flag.
            // We create a minimal CSS input that imports Tailwind with source(none)
            // to disable automatic scanning, then explicitly point to our content file.
            $inputCss = $this->buildInputCss($contentFile);
            file_put_contents($inputCssFile, $inputCss);

            $args = [
                $this->binary,
                '--input', $inputCssFile,
                '--output', '-',
            ];

            if ($this->configPath !== null) {
                $args[] = '--config';
                $args[] = $this->configPath;
            }

            $process = new Process($args);
            $process->setTimeout($this->timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                $error = $process->getErrorOutput() ?: $process->getOutput();

                throw new RenderException("Tailwind CSS compilation failed: {$error}");
            }

            return $process->getOutput();
        } finally {
            if (file_exists($contentFile)) {
                unlink($contentFile);
            }
            if (file_exists($inputCssFile)) {
                unlink($inputCssFile);
            }
        }
    }

    /**
     * Build the input CSS content for the Tailwind CLI.
     *
     * Tailwind v4 no longer supports the --content CLI flag.
     * Instead, we use the @source directive in CSS to point
     * to the specific content file we want to scan.
     */
    protected function buildInputCss(string $contentFilePath): string
    {
        // Use source(none) to disable automatic directory scanning,
        // then @source the specific temp file containing our HTML.
        return <<<CSS
        @import "tailwindcss" source(none);
        @source "{$contentFilePath}";
        CSS;
    }
}
