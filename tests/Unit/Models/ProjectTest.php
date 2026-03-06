<?php

use PdfStudio\Laravel\Models\Project;

it('has correct table name', function () {
    $model = new Project;
    expect($model->getTable())->toBe('pdf_studio_projects');
});

it('has correct fillable attributes', function () {
    $model = new Project;
    expect($model->getFillable())->toBe(['workspace_id', 'name', 'slug', 'description']);
});
