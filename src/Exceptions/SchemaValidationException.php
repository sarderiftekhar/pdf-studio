<?php

namespace PdfStudio\Laravel\Exceptions;

class SchemaValidationException extends \RuntimeException
{
    public static function emptyBlocks(): self
    {
        return new self('Document schema must contain at least one block.');
    }

    public static function unsupportedVersion(string $version): self
    {
        return new self("Unsupported schema version: {$version}. Supported: 1.0.");
    }

    public static function invalidJson(string $message): self
    {
        return new self("Invalid JSON: {$message}");
    }
}
