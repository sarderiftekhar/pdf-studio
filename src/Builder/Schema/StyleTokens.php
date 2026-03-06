<?php

namespace PdfStudio\Laravel\Builder\Schema;

class StyleTokens
{
    public function __construct(
        public string $primaryColor = '#000000',
        public string $fontFamily = 'sans-serif',
        public string $fontSize = '16px',
        public string $lineHeight = '1.5',
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'primary_color' => $this->primaryColor,
            'font_family' => $this->fontFamily,
            'font_size' => $this->fontSize,
            'line_height' => $this->lineHeight,
        ];
    }

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            primaryColor: $data['primary_color'] ?? '#000000',
            fontFamily: $data['font_family'] ?? 'sans-serif',
            fontSize: $data['font_size'] ?? '16px',
            lineHeight: $data['line_height'] ?? '1.5',
        );
    }
}
