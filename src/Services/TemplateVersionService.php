<?php

namespace PdfStudio\Laravel\Services;

use Illuminate\Support\Collection;
use PdfStudio\Laravel\Contracts\TemplateVersionServiceContract;
use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Exceptions\TemplateNotFoundException;
use PdfStudio\Laravel\Models\TemplateVersion;

class TemplateVersionService implements TemplateVersionServiceContract
{
    public function create(TemplateDefinition $definition, string $author, ?string $changeNotes = null): TemplateVersion
    {
        $nextVersion = TemplateVersion::where('template_name', $definition->name)
            ->max('version_number') + 1;

        return TemplateVersion::create([
            'template_name' => $definition->name,
            'version_number' => $nextVersion,
            'view' => $definition->view,
            'description' => $definition->description,
            'default_options' => $definition->defaultOptions,
            'data_provider' => $definition->dataProvider,
            'author' => $author,
            'change_notes' => $changeNotes,
        ]);
    }

    /** @return Collection<int, TemplateVersion> */
    public function list(string $templateName): Collection
    {
        return TemplateVersion::where('template_name', $templateName)
            ->orderByDesc('version_number')
            ->get();
    }

    public function restore(string $templateName, int $versionNumber): TemplateDefinition
    {
        $version = TemplateVersion::where('template_name', $templateName)
            ->where('version_number', $versionNumber)
            ->first();

        if ($version === null) {
            throw TemplateNotFoundException::forName("{$templateName} v{$versionNumber}");
        }

        return $version->toDefinition();
    }

    /** @return array{from_version: int, to_version: int, changes: array<string, array{from: mixed, to: mixed}>} */
    public function diff(string $templateName, int $fromVersion, int $toVersion): array
    {
        $from = TemplateVersion::where('template_name', $templateName)
            ->where('version_number', $fromVersion)
            ->first();

        $to = TemplateVersion::where('template_name', $templateName)
            ->where('version_number', $toVersion)
            ->first();

        if ($from === null || $to === null) {
            throw TemplateNotFoundException::forName("{$templateName} v{$fromVersion}..v{$toVersion}");
        }

        $fields = ['view', 'description', 'default_options', 'data_provider'];
        $changes = [];

        foreach ($fields as $field) {
            if ($from->{$field} !== $to->{$field}) {
                $changes[$field] = [
                    'from' => $from->{$field},
                    'to' => $to->{$field},
                ];
            }
        }

        return [
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'changes' => $changes,
        ];
    }
}
