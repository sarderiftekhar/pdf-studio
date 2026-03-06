<?php

namespace PdfStudio\Laravel\Events;

final class RenderCompleted
{
    public function __construct(
        public string $driver,
        public float $renderTimeMs,
        public int $bytes,
    ) {}
}
