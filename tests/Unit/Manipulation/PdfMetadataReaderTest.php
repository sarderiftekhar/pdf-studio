<?php

use PdfStudio\Laravel\Manipulation\PdfMetadataReader;

it('returns an empty array when no info dictionary is present', function () {
    $reader = new PdfMetadataReader;

    expect($reader->read('%PDF-1.7'))->toBe([]);
});

it('extracts metadata from a basic info dictionary', function () {
    $reader = new PdfMetadataReader;

    $pdf = <<<'PDF'
%PDF-1.7
1 0 obj
<< /Type /Catalog >>
endobj
2 0 obj
<< /Title (Annual Report) /Author (PDF Studio) /Subject (Quarterly Review) >>
endobj
trailer
<< /Root 1 0 R /Info 2 0 R >>
%%EOF
PDF;

    expect($reader->read($pdf))->toBe([
        'Title' => 'Annual Report',
        'Author' => 'PDF Studio',
        'Subject' => 'Quarterly Review',
    ]);
});

it('decodes escaped and hex metadata values', function () {
    $reader = new PdfMetadataReader;

    $pdf = <<<'PDF'
%PDF-1.7
3 0 obj
<< /Title (Annual \(North\)) /Keywords <5064662053747564696f> >>
endobj
trailer
<< /Info 3 0 R >>
%%EOF
PDF;

    expect($reader->read($pdf))->toBe([
        'Title' => 'Annual (North)',
        'Keywords' => 'Pdf Studio',
    ]);
});
