<?php

use PdfStudio\Laravel\Drivers\CapabilityValidator;
use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

it('returns empty warnings when all options are supported', function () {
    $capabilities = new DriverCapabilities(
        landscape: true,
        customMargins: true,
        headerFooter: true,
        printBackground: true,
    );
    $options = new RenderOptions(landscape: true);

    $warnings = CapabilityValidator::validate($options, $capabilities);

    expect($warnings)->toBeEmpty();
});

it('warns when header/footer is not supported', function () {
    $capabilities = new DriverCapabilities(headerFooter: false);
    $options = new RenderOptions(headerHtml: '<p>Header</p>');

    $warnings = CapabilityValidator::validate($options, $capabilities);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('header');
});

it('warns when format is not in supported list', function () {
    $capabilities = new DriverCapabilities(supportedFormats: ['A4', 'Letter']);
    $options = new RenderOptions(format: 'Tabloid');

    $warnings = CapabilityValidator::validate($options, $capabilities);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('Tabloid');
});

it('returns multiple warnings for multiple unsupported options', function () {
    $capabilities = new DriverCapabilities(
        headerFooter: false,
        printBackground: false,
    );
    $options = new RenderOptions(
        headerHtml: '<p>Header</p>',
        printBackground: true,
    );

    $warnings = CapabilityValidator::validate($options, $capabilities);

    expect($warnings)->toHaveCount(2);
});
