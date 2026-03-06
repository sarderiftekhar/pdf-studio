<?php

it('binds TemplateVersionService when pro is enabled', function () {
    config(['pdf-studio.pro.enabled' => true]);

    $service = app(\PdfStudio\Laravel\Contracts\TemplateVersionServiceContract::class);

    expect($service)->toBeInstanceOf(\PdfStudio\Laravel\Services\TemplateVersionService::class);
});

it('binds AccessControl contract', function () {
    $service = app(\PdfStudio\Laravel\Contracts\AccessControlContract::class);

    expect($service)->toBeInstanceOf(\PdfStudio\Laravel\Services\AccessControl::class);
});
