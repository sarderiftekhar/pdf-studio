<?php

use PdfStudio\Laravel\Facades\Pdf;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('sets headerExceptFirst option', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->headerExceptFirst();

    expect($builder->getContext()->options->headerExceptFirst)->toBeTrue();
});

it('sets footerExceptLast option', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->footerExceptLast();

    expect($builder->getContext()->options->footerExceptLast)->toBeTrue();
});

it('sets headerOnPages option', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->headerOnPages([1, 3, 5]);

    expect($builder->getContext()->options->headerOnPages)->toBe([1, 3, 5]);
});

it('sets headerExcludePages option', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->headerExcludePages([2, 4]);

    expect($builder->getContext()->options->headerExcludePages)->toBe([2, 4]);
});

it('sets footerExcludePages option', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $builder->html('<h1>Test</h1>')->footerExcludePages([1, 5]);

    expect($builder->getContext()->options->footerExcludePages)->toBe([1, 5]);
});

it('defaults to no header/footer exclusions', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);
    $builder->html('<h1>Test</h1>');

    $options = $builder->getContext()->options;

    expect($options->headerExceptFirst)->toBeFalse()
        ->and($options->footerExceptLast)->toBeFalse()
        ->and($options->headerOnPages)->toBeNull()
        ->and($options->headerExcludePages)->toBe([])
        ->and($options->footerExcludePages)->toBe([]);
});

it('supports chaining multiple header/footer options', function () {
    $builder = app(\PdfStudio\Laravel\PdfBuilder::class);

    $result = $builder->html('<h1>Test</h1>')
        ->headerExceptFirst()
        ->footerExceptLast()
        ->headerExcludePages([3])
        ->footerExcludePages([1, 2]);

    expect($result)->toBeInstanceOf(\PdfStudio\Laravel\PdfBuilder::class);

    $options = $builder->getContext()->options;

    expect($options->headerExceptFirst)->toBeTrue()
        ->and($options->footerExceptLast)->toBeTrue()
        ->and($options->headerExcludePages)->toBe([3])
        ->and($options->footerExcludePages)->toBe([1, 2]);
});

it('renders with header/footer control using fake driver', function () {
    $result = Pdf::html('<h1>Test</h1>')
        ->headerExceptFirst()
        ->footerExceptLast()
        ->render();

    expect($result->content())->toContain('FAKE_PDF');
});
