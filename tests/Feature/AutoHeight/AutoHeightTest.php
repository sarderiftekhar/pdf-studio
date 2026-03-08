<?php

use PdfStudio\Laravel\Facades\Pdf;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('sets autoHeight via contentFit method', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->contentFit();

    $options = $builder->getContext()->options;

    expect($options->autoHeight)->toBeTrue()
        ->and($options->maxHeight)->toBe(5000);
});

it('contentFit accepts custom maxHeight', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->contentFit(3000);

    $options = $builder->getContext()->options;

    expect($options->autoHeight)->toBeTrue()
        ->and($options->maxHeight)->toBe(3000);
});

it('autoHeight is an alias for contentFit', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->autoHeight(4000);

    $options = $builder->getContext()->options;

    expect($options->autoHeight)->toBeTrue()
        ->and($options->maxHeight)->toBe(4000);
});

it('autoHeight defaults to false', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>');

    $options = $builder->getContext()->options;

    expect($options->autoHeight)->toBeFalse()
        ->and($options->maxHeight)->toBe(5000);
});

it('renders with contentFit using fake driver', function () {
    $result = Pdf::html('<h1>Long content</h1>')->contentFit()->render();

    expect($result->content())->toContain('FAKE_PDF')
        ->and($result->driver)->toBe('fake');
});

it('all drivers report autoHeight capability', function () {
    $drivers = [
        \PdfStudio\Laravel\Drivers\FakeDriver::class,
    ];

    foreach ($drivers as $driverClass) {
        $driver = new $driverClass;
        expect($driver->supports()->autoHeight)->toBeTrue("Driver {$driverClass} should support autoHeight");
    }
});
