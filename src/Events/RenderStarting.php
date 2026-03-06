<?php

namespace PdfStudio\Laravel\Events;

final class RenderStarting
{
    public function __construct(
        public string $html,
        public string $driver,
        public ?string $viewName = null,
    ) {}
}
