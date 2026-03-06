<?php

namespace PdfStudio\Laravel\Events;

class RenderStarting
{
    public function __construct(
        public string $html,
        public string $driver,
        public ?string $viewName = null,
    ) {}
}
