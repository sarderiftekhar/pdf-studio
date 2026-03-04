<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

interface RendererContract
{
    /**
     * Render HTML content to PDF bytes.
     */
    public function render(string $html, RenderOptions $options): string;

    /**
     * Return the capabilities of this driver.
     */
    public function supports(): DriverCapabilities;
}
