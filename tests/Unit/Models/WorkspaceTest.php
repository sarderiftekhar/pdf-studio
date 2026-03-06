<?php

use PdfStudio\Laravel\Models\Workspace;

it('has correct table name', function () {
    $model = new Workspace;
    expect($model->getTable())->toBe('pdf_studio_workspaces');
});

it('has correct fillable attributes', function () {
    $model = new Workspace;
    expect($model->getFillable())->toBe(['name', 'slug']);
});
