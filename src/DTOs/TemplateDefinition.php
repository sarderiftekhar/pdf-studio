<?php

namespace PdfStudio\Laravel\DTOs;

class TemplateDefinition
{
    /**
     * @param  array<string, mixed>  $defaultOptions
     */
    public function __construct(
        public string $name,
        public string $view,
        public ?string $description = null,
        public array $defaultOptions = [],
        public ?string $dataProvider = null,
    ) {}
}
