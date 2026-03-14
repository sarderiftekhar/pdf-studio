<?php

namespace PdfStudio\Laravel\Fonts;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\DTOs\FontDefinition;

class FontRegistry
{
    /** @var array<string, FontDefinition> */
    protected array $fonts = [];

    public function __construct(Application $app)
    {
        /** @var array<string, array<string, mixed>> $configuredFonts */
        $configuredFonts = $app['config']->get('pdf-studio.fonts', []);

        foreach ($configuredFonts as $name => $config) {
            $sources = $config['sources'] ?? [];

            if (!is_array($sources)) {
                continue;
            }

            $this->register(new FontDefinition(
                name: $name,
                family: (string) ($config['family'] ?? $name),
                sources: array_values(array_filter($sources, static fn ($source): bool => is_string($source) && $source !== '')),
                weight: (string) ($config['weight'] ?? 'normal'),
                style: (string) ($config['style'] ?? 'normal'),
            ));
        }
    }

    public function register(FontDefinition $font): void
    {
        $this->fonts[$font->name] = $font;
    }

    public function has(string $name): bool
    {
        return isset($this->fonts[$name]);
    }

    public function get(string $name): ?FontDefinition
    {
        return $this->fonts[$name] ?? null;
    }

    /**
     * @return array<string, FontDefinition>
     */
    public function all(): array
    {
        return $this->fonts;
    }
}
