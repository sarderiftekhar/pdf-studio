<?php

namespace PdfStudio\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use PdfStudio\Laravel\DTOs\TemplateDefinition;

class TemplateVersion extends Model
{
    protected $table = 'pdf_studio_template_versions';

    protected $fillable = [
        'template_name',
        'version_number',
        'view',
        'description',
        'default_options',
        'data_provider',
        'author',
        'change_notes',
    ];

    protected $casts = [
        'default_options' => 'array',
        'version_number' => 'integer',
    ];

    public function toDefinition(): TemplateDefinition
    {
        return new TemplateDefinition(
            name: $this->template_name,
            view: $this->view,
            description: $this->description,
            defaultOptions: $this->default_options ?? [],
            dataProvider: $this->data_provider,
        );
    }
}
