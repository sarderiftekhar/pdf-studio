<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\PdfBuilder;

it('sets view name via fluent API', function () {
    $builder = Pdf::view('invoices.show');

    expect($builder)->toBeInstanceOf(PdfBuilder::class)
        ->and($builder->getContext()->viewName)->toBe('invoices.show');
});

it('sets raw HTML via fluent API', function () {
    $builder = Pdf::html('<h1>Test</h1>');

    expect($builder->getContext()->rawHtml)->toBe('<h1>Test</h1>')
        ->and($builder->getContext()->viewName)->toBeNull();
});

it('clears view when html is set and vice versa', function () {
    $builder = Pdf::view('test.view')->html('<h1>Override</h1>');

    expect($builder->getContext()->viewName)->toBeNull()
        ->and($builder->getContext()->rawHtml)->toBe('<h1>Override</h1>');
});

it('chains data correctly', function () {
    $builder = Pdf::view('invoices.show')
        ->data(['invoice' => ['id' => 1]]);

    expect($builder->getContext()->data)->toBe(['invoice' => ['id' => 1]]);
});

it('sets driver override', function () {
    $builder = Pdf::view('test')->driver('dompdf');

    expect($builder->getResolvedDriverName())->toBe('dompdf');
});

it('uses default driver when none specified', function () {
    $builder = Pdf::view('test');

    expect($builder->getResolvedDriverName())->toBe('chromium');
});

it('sets format', function () {
    $builder = Pdf::view('test')->format('Letter');

    expect($builder->getContext()->options->format)->toBe('Letter');
});

it('sets landscape', function () {
    $builder = Pdf::view('test')->landscape();

    expect($builder->getContext()->options->landscape)->toBeTrue();
});

it('sets margins', function () {
    $builder = Pdf::view('test')->margins(top: 20, bottom: 30);

    $margins = $builder->getContext()->options->margins;

    expect($margins['top'])->toBe(20)
        ->and($margins['bottom'])->toBe(30)
        ->and($margins['right'])->toBe(10)
        ->and($margins['left'])->toBe(10);
});

it('supports full method chaining', function () {
    $builder = Pdf::view('invoices.show')
        ->data(['invoice' => ['id' => 1]])
        ->driver('chromium')
        ->format('A4')
        ->landscape()
        ->margins(top: 20, bottom: 20);

    expect($builder->getContext()->viewName)->toBe('invoices.show')
        ->and($builder->getContext()->data)->toBe(['invoice' => ['id' => 1]])
        ->and($builder->getResolvedDriverName())->toBe('chromium')
        ->and($builder->getContext()->options->format)->toBe('A4')
        ->and($builder->getContext()->options->landscape)->toBeTrue()
        ->and($builder->getContext()->options->margins['top'])->toBe(20);
});
