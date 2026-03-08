<?php

namespace PdfStudio\Laravel\DTOs;

class WatermarkOptions
{
    public function __construct(
        public ?string $text = null,
        public ?string $imagePath = null,
        public float $opacity = 0.3,
        public int $rotation = -45,
        public string $position = 'center',
        public int $fontSize = 48,
        public string $color = '#999999',
    ) {}
}
