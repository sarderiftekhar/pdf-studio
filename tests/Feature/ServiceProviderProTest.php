<?php

use PdfStudio\Laravel\Contracts\AccessControlContract;
use PdfStudio\Laravel\Contracts\TemplateVersionServiceContract;
use PdfStudio\Laravel\Services\AccessControl;
use PdfStudio\Laravel\Services\TemplateVersionService;

it('binds TemplateVersionService when pro is enabled', function () {
    config(['pdf-studio.pro.enabled' => true]);

    $service = app(TemplateVersionServiceContract::class);

    expect($service)->toBeInstanceOf(TemplateVersionService::class);
});

it('binds AccessControl contract', function () {
    $service = app(AccessControlContract::class);

    expect($service)->toBeInstanceOf(AccessControl::class);
});
