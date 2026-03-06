<?php

namespace PdfStudio\Laravel\Pipeline;

use Closure;
use PdfStudio\Laravel\Drivers\DriverManager;
use PdfStudio\Laravel\DTOs\RenderContext;

class PdfRenderer
{
    protected ?string $driverName = null;

    public function __construct(
        protected DriverManager $driverManager,
    ) {}

    public function setDriver(string $name): static
    {
        $this->driverName = $name;

        return $this;
    }

    public function handle(RenderContext $context, Closure $next): RenderContext
    {
        $html = $context->styledHtml ?? $context->compiledHtml ?? '';
        $driver = $this->driverManager->driver($this->driverName);

        $context->pdfContent = $driver->render($html, $context->options);

        return $next($context);
    }
}
