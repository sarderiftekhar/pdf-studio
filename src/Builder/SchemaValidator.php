<?php

namespace PdfStudio\Laravel\Builder;

use PdfStudio\Laravel\Builder\Schema\DocumentSchema;
use PdfStudio\Laravel\Exceptions\SchemaValidationException;

class SchemaValidator
{
    /** @var array<int, string> */
    protected array $supportedVersions = ['1.0'];

    public function validate(DocumentSchema $schema): true
    {
        if (!in_array($schema->version, $this->supportedVersions, true)) {
            throw SchemaValidationException::unsupportedVersion($schema->version);
        }

        if (count($schema->blocks) === 0) {
            throw SchemaValidationException::emptyBlocks();
        }

        return true;
    }

    public function validateJson(string $json): true
    {
        try {
            $schema = DocumentSchema::fromJson($json);
        } catch (\JsonException $e) {
            throw SchemaValidationException::invalidJson($e->getMessage());
        }

        return $this->validate($schema);
    }
}
