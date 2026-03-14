<?php

namespace PdfStudio\Laravel\Fonts;

class FontCssGenerator
{
    public function __construct(
        protected FontRegistry $fonts,
    ) {}

    public function generate(): string
    {
        $blocks = [];

        foreach ($this->fonts->all() as $font) {
            $sources = [];

            foreach ($font->sources as $source) {
                if (!is_file($source) || !is_readable($source)) {
                    continue;
                }

                $content = file_get_contents($source);

                if ($content === false) {
                    continue;
                }

                $mime = $this->mimeType($source);
                $format = $this->formatHint($source);
                $base64 = base64_encode($content);

                $sources[] = "url(\"data:{$mime};base64,{$base64}\") format(\"{$format}\")";
            }

            if ($sources === []) {
                continue;
            }

            $family = str_replace(['\\', '"'], ['\\\\', '\\"'], $font->family);
            $weight = str_replace(['\\', '"'], ['\\\\', '\\"'], $font->weight);
            $style = str_replace(['\\', '"'], ['\\\\', '\\"'], $font->style);

            $blocks[] = "@font-face{font-family:\"{$family}\";src:".implode(',', $sources).";font-weight:{$weight};font-style:{$style};font-display:swap;}";
        }

        return implode("\n", $blocks);
    }

    protected function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
    }

    protected function formatHint(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'ttf' => 'truetype',
            'otf' => 'opentype',
            'woff' => 'woff',
            'woff2' => 'woff2',
            default => 'unknown',
        };
    }
}
