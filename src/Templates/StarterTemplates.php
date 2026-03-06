<?php

namespace PdfStudio\Laravel\Templates;

use PdfStudio\Laravel\DTOs\TemplateDefinition;

class StarterTemplates
{
    /**
     * @return array<TemplateDefinition>
     */
    public static function definitions(): array
    {
        return [
            new TemplateDefinition(
                name: 'pdf-studio::invoice',
                view: 'pdf-studio::invoice',
                description: 'Standard invoice with line items and totals',
            ),
            new TemplateDefinition(
                name: 'pdf-studio::report',
                view: 'pdf-studio::report',
                description: 'Simple report with titled sections',
            ),
            new TemplateDefinition(
                name: 'pdf-studio::certificate',
                view: 'pdf-studio::certificate',
                description: 'Achievement certificate with decorative border',
                defaultOptions: ['landscape' => true],
            ),
        ];
    }
}
