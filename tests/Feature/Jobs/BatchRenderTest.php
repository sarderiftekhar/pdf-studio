<?php

use Illuminate\Support\Facades\Bus;
use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\Jobs\RenderPdfJob;

it('dispatches batch render jobs', function () {
    Bus::fake();

    Pdf::batch([
        ['view' => 'simple', 'data' => ['name' => 'A'], 'outputPath' => 'a.pdf'],
        ['view' => 'simple', 'data' => ['name' => 'B'], 'outputPath' => 'b.pdf'],
        ['view' => 'simple', 'data' => ['name' => 'C'], 'outputPath' => 'c.pdf'],
    ]);

    Bus::assertDispatched(RenderPdfJob::class, 3);
});

it('dispatches batch jobs with shared options', function () {
    Bus::fake();

    Pdf::batch([
        ['view' => 'simple', 'data' => ['name' => 'A'], 'outputPath' => 'a.pdf'],
        ['view' => 'simple', 'data' => ['name' => 'B'], 'outputPath' => 'b.pdf'],
    ], driver: 'dompdf', disk: 's3');

    Bus::assertDispatched(RenderPdfJob::class, function ($job) {
        return $job->driver === 'dompdf' && $job->disk === 's3';
    });
});
