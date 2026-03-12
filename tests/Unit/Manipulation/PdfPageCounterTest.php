<?php

use PdfStudio\Laravel\Exceptions\ManipulationException;
use PdfStudio\Laravel\Manipulation\PdfPageCounter;

it('throws when fpdi is not available for page counting', function () {
    $counter = new class extends PdfPageCounter
    {
        protected function ensureFpdiAvailable(): void
        {
            throw new ManipulationException(
                'PDF page counting requires setasign/fpdi. Install it with: composer require setasign/fpdi'
            );
        }
    };

    $counter->count('%PDF-fake');
})->throws(ManipulationException::class, 'PDF page counting requires');

it('returns the page count when counting is stubbed', function () {
    $counter = new class extends PdfPageCounter
    {
        protected function ensureFpdiAvailable(): void
        {
        }

        protected function countFromFile(string $inputFile): int
        {
            expect(is_file($inputFile))->toBeTrue();

            return 12;
        }
    };

    expect($counter->count('%PDF-fake'))->toBe(12);
});
