<?php

namespace PdfStudio\Laravel\Events;

final class RenderFailed
{
    public function __construct(
        public string $driver,
        public \Throwable $exception,
    ) {}
}
