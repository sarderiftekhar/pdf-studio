<?php

namespace PdfStudio\Laravel\Exceptions;

class TemplateNotFoundException extends \RuntimeException
{
    public static function forName(string $name): self
    {
        return new self("Template [{$name}] is not registered.");
    }
}
