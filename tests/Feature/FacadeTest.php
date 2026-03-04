<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\PdfBuilder;

it('resolves to PdfBuilder', function () {
    expect(Pdf::getFacadeRoot())->toBeInstanceOf(PdfBuilder::class);
});
