<?php

namespace PdfStudio\Laravel\Events;

class RenderCompleted
{
    public function __construct(
        public string $driver,
        public float $renderTimeMs,
        public int $bytes,
    ) {}
}
