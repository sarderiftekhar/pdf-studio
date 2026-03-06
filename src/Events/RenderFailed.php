<?php

namespace PdfStudio\Laravel\Events;

class RenderFailed
{
    public function __construct(
        public string $driver,
        public \Throwable $exception,
    ) {}
}
