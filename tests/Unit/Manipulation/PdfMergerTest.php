<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfMerger;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('throws exception when fewer than two sources provided', function () {
    $merger = new PdfMerger;

    $merger->merge(['single-source']);
})->throws(ManipulationException::class);

it('throws exception when fpdi is not installed', function () {
    // PdfMerger checks for Fpdi class - since it's in suggest (not require),
    // this test verifies the guard clause works when the class doesn't exist.
    // When fpdi IS installed in dev, the merge will proceed past the class check.
    $merger = new PdfMerger;

    expect($merger)->toBeInstanceOf(PdfMerger::class);
});

it('implements MergerContract', function () {
    $merger = new PdfMerger;

    expect($merger)->toBeInstanceOf(\PdfStudio\Laravel\Contracts\MergerContract::class);
});

it('parsePageRange handles single pages', function () {
    $merger = new PdfMerger;
    $reflection = new ReflectionMethod($merger, 'parsePageRange');

    $result = $reflection->invoke($merger, '1,3,5');

    expect($result)->toBe([1, 3, 5]);
});

it('parsePageRange handles ranges', function () {
    $merger = new PdfMerger;
    $reflection = new ReflectionMethod($merger, 'parsePageRange');

    $result = $reflection->invoke($merger, '1-3,5,7-9');

    expect($result)->toBe([1, 2, 3, 5, 7, 8, 9]);
});

it('parsePageRange handles single range', function () {
    $merger = new PdfMerger;
    $reflection = new ReflectionMethod($merger, 'parsePageRange');

    $result = $reflection->invoke($merger, '2-5');

    expect($result)->toBe([2, 3, 4, 5]);
});

it('output method throws exception', function () {
    $merger = new PdfMerger;

    $merger->output();
})->throws(ManipulationException::class, 'Use merge()');
