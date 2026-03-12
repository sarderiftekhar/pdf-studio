<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\Exceptions\RenderException;

class AssetResolver
{
    public function __construct(
        protected Application $app,
    ) {}

    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        $html = $context->compiledHtml ?? '';

        if ($html === '') {
            return $next($context);
        }

        $context->compiledHtml = $this->resolveHtml($html);

        return $next($context);
    }

    public function resolveHtml(string $html): string
    {
        if (!$this->containsResolvableAssets($html)) {
            return $html;
        }

        if (!class_exists(\DOMDocument::class)) {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $this->resolveImageSources($dom);
        $this->resolveStylesheets($dom);

        return (string) $dom->saveHTML();
    }

    protected function containsResolvableAssets(string $html): bool
    {
        return preg_match('/<(img|link)\b/i', $html) === 1;
    }

    protected function resolveImageSources(\DOMDocument $dom): void
    {
        /** @var \DOMNodeList<\DOMElement> $images */
        $images = $dom->getElementsByTagName('img');

        foreach ($images as $image) {
            $src = $image->getAttribute('src');

            if ($src === '') {
                continue;
            }

            if ($this->isRemoteUrl($src)) {
                $this->assertRemoteAllowed($src);

                continue;
            }

            if ($this->isIgnoredSource($src)) {
                continue;
            }

            if (!$this->inlineLocalAssets()) {
                continue;
            }

            $path = $this->resolveLocalPath($src);

            if ($path === null) {
                continue;
            }

            $dataUri = $this->toDataUri($path);

            if ($dataUri !== null) {
                $image->setAttribute('src', $dataUri);
            }
        }
    }

    protected function resolveStylesheets(\DOMDocument $dom): void
    {
        /** @var \DOMNodeList<\DOMElement> $links */
        $links = $dom->getElementsByTagName('link');

        $replacements = [];

        foreach ($links as $link) {
            if (strtolower($link->getAttribute('rel')) !== 'stylesheet') {
                continue;
            }

            $href = $link->getAttribute('href');

            if ($href === '') {
                continue;
            }

            if ($this->isRemoteUrl($href)) {
                $this->assertRemoteAllowed($href);

                continue;
            }

            if ($this->isIgnoredSource($href) || !$this->inlineLocalAssets()) {
                continue;
            }

            $path = $this->resolveLocalPath($href);

            if ($path === null) {
                continue;
            }

            $css = file_get_contents($path);

            if ($css === false) {
                continue;
            }

            $style = $dom->createElement('style', $css);
            $replacements[] = [$link, $style];
        }

        foreach ($replacements as [$link, $style]) {
            $link->parentNode?->replaceChild($style, $link);
        }
    }

    protected function isIgnoredSource(string $source): bool
    {
        return str_starts_with($source, 'data:')
            || str_starts_with($source, '#')
            || str_starts_with($source, 'mailto:');
    }

    protected function isRemoteUrl(string $source): bool
    {
        return preg_match('#^https?://#i', $source) === 1;
    }

    protected function assertRemoteAllowed(string $source): void
    {
        if (!$this->allowRemoteAssets()) {
            throw new RenderException("Remote asset loading is disabled for [{$source}].");
        }

        $allowedHosts = $this->allowedRemoteHosts();

        if ($allowedHosts === []) {
            return;
        }

        $host = parse_url($source, PHP_URL_HOST);

        if (!is_string($host) || $host === '' || !in_array(strtolower($host), $allowedHosts, true)) {
            throw new RenderException("Remote asset host is not allowed for [{$source}].");
        }
    }

    protected function inlineLocalAssets(): bool
    {
        return (bool) $this->app['config']->get('pdf-studio.assets.inline_local', true);
    }

    protected function allowRemoteAssets(): bool
    {
        return (bool) $this->app['config']->get('pdf-studio.assets.allow_remote', true);
    }

    /**
     * @return array<int, string>
     */
    protected function allowedRemoteHosts(): array
    {
        /** @var array<int, mixed> $hosts */
        $hosts = $this->app['config']->get('pdf-studio.assets.allowed_hosts', []);

        return array_values(array_map(
            static fn (string $host): string => strtolower($host),
            array_filter($hosts, static fn ($host): bool => is_string($host) && $host !== '')
        ));
    }

    protected function resolveLocalPath(string $source): ?string
    {
        $candidates = [];

        if (str_starts_with($source, '/')) {
            $candidates[] = $source;
        }

        $candidates[] = $this->app->basePath($source);
        $candidates[] = getcwd().DIRECTORY_SEPARATOR.ltrim($source, DIRECTORY_SEPARATOR);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function toDataUri(string $path): ?string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $mime = $this->mimeType($path);

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }

    protected function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'css' => 'text/css',
            default => 'application/octet-stream',
        };
    }
}
