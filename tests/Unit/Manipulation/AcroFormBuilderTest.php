<?php

use PdfStudio\Laravel\Manipulation\AcroFormBuilder;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('stores field values via fill method', function () {
    $builder = new AcroFormBuilder($this->app, '/path/to/form.pdf');

    $result = $builder->fill(['name' => 'John', 'email' => 'john@example.com']);

    expect($result)->toBeInstanceOf(AcroFormBuilder::class);
});

it('supports fluent chaining', function () {
    $builder = new AcroFormBuilder($this->app, '/path/to/form.pdf');

    $result = $builder
        ->fill(['name' => 'John'])
        ->flatten();

    expect($result)->toBeInstanceOf(AcroFormBuilder::class);
});

it('merges multiple fill calls', function () {
    $builder = new AcroFormBuilder($this->app, '/path/to/form.pdf');

    $builder->fill(['name' => 'John'])->fill(['email' => 'john@example.com']);

    $reflection = new ReflectionProperty($builder, 'fieldValues');
    $values = $reflection->getValue($builder);

    expect($values)->toBe(['name' => 'John', 'email' => 'john@example.com']);
});

it('sets flatten flag', function () {
    $builder = new AcroFormBuilder($this->app, '/path/to/form.pdf');

    $builder->flatten();

    $reflection = new ReflectionProperty($builder, 'flatten');
    expect($reflection->getValue($builder))->toBeTrue();
});

it('flatten defaults to false', function () {
    $builder = new AcroFormBuilder($this->app, '/path/to/form.pdf');

    $reflection = new ReflectionProperty($builder, 'flatten');
    expect($reflection->getValue($builder))->toBeFalse();
});
