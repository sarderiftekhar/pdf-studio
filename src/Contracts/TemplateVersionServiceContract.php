<?php

namespace PdfStudio\Laravel\Contracts;

use Illuminate\Support\Collection;
use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Models\TemplateVersion;

interface TemplateVersionServiceContract
{
    public function create(TemplateDefinition $definition, string $author, ?string $changeNotes = null): TemplateVersion;

    /** @return Collection<int, TemplateVersion> */
    public function list(string $templateName): Collection;

    public function restore(string $templateName, int $versionNumber): TemplateDefinition;

    /** @return array{from_version: int, to_version: int, changes: array<string, array{from: mixed, to: mixed}>} */
    public function diff(string $templateName, int $fromVersion, int $toVersion): array;
}
