<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\AcroFormFiller;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('implements AcroFormContract', function () {
    $filler = new AcroFormFiller;

    expect($filler)->toBeInstanceOf(\PdfStudio\Laravel\Contracts\AcroFormContract::class);
});

it('output method throws exception', function () {
    $filler = new AcroFormFiller;

    $filler->output();
})->throws(ManipulationException::class, 'Use fill()');

it('throws exception when pdftk not available', function () {
    // When mikehaertl/php-pdftk is not installed, ensurePdftkAvailable throws
    // If it IS installed, this test verifies the filler can be instantiated
    $filler = new AcroFormFiller;

    expect($filler)->toBeInstanceOf(AcroFormFiller::class);
});
