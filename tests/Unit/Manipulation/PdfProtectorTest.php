<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfProtector;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

it('implements ProtectorContract', function () {
    $protector = new PdfProtector;

    expect($protector)->toBeInstanceOf(\PdfStudio\Laravel\Contracts\ProtectorContract::class);
});

it('output method throws exception', function () {
    $protector = new PdfProtector;

    $protector->output();
})->throws(ManipulationException::class, 'Use protect()');

it('can be instantiated', function () {
    $protector = new PdfProtector;

    expect($protector)->toBeInstanceOf(PdfProtector::class);
});
