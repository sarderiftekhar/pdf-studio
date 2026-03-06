<?php

namespace PdfStudio\Laravel\Templates;

use PdfStudio\Laravel\DTOs\TemplateDefinition;
use PdfStudio\Laravel\Exceptions\TemplateNotFoundException;

class TemplateRegistry
{
    /** @var array<string, TemplateDefinition> */
    protected array $templates = [];

    public function register(TemplateDefinition $definition): void
    {
        $this->templates[$definition->name] = $definition;
    }

    public function get(string $name): TemplateDefinition
    {
        if (! isset($this->templates[$name])) {
            throw TemplateNotFoundException::forName($name);
        }

        return $this->templates[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    /**
     * @return array<string, TemplateDefinition>
     */
    public function all(): array
    {
        return $this->templates;
    }
}
