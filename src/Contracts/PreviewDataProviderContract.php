<?php

namespace PdfStudio\Laravel\Contracts;

interface PreviewDataProviderContract
{
    /**
     * Return sample data for template preview.
     *
     * @return array<string, mixed>
     */
    public function data(): array;
}
