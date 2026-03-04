<?php

namespace PdfStudio\Laravel\Contracts;

interface CssCompilerContract
{
    /**
     * Compile CSS for the given HTML content.
     */
    public function compile(string $html): string;
}
