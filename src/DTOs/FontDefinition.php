<?php

namespace PdfStudio\Laravel\DTOs;

class FontDefinition
{
    /**
     * @param  array<int, string>  $sources
     */
    public function __construct(
        public string $name,
        public string $family,
        public array $sources,
        public string $weight = 'normal',
        public string $style = 'normal',
    ) {}
}
