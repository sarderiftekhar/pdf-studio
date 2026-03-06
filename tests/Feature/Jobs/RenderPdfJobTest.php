<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PdfStudio\Laravel\Jobs\RenderPdfJob;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('implements ShouldQueue', function () {
    $job = new RenderPdfJob(
        view: 'simple',
        data: ['name' => 'Test'],
        outputPath: 'test.pdf',
    );

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('renders and saves PDF to storage when handled', function () {
    Storage::fake('local');
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $job = new RenderPdfJob(
        view: 'simple',
        data: ['name' => 'World'],
        outputPath: 'output/test.pdf',
    );

    $job->handle($this->app);

    Storage::disk('local')->assertExists('output/test.pdf');
});

it('uses configured driver and options', function () {
    Storage::fake('local');
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $job = new RenderPdfJob(
        view: 'simple',
        data: ['name' => 'Test'],
        outputPath: 'output/test.pdf',
        driver: 'fake',
        options: ['format' => 'Letter', 'landscape' => true],
    );

    $job->handle($this->app);

    Storage::disk('local')->assertExists('output/test.pdf');
});

it('uses configured disk', function () {
    Storage::fake('s3');
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $job = new RenderPdfJob(
        view: 'simple',
        data: ['name' => 'Test'],
        outputPath: 'reports/test.pdf',
        disk: 's3',
    );

    $job->handle($this->app);

    Storage::disk('s3')->assertExists('reports/test.pdf');
});

it('can be dispatched to a queue', function () {
    Bus::fake();

    RenderPdfJob::dispatch(
        view: 'simple',
        data: ['name' => 'Test'],
        outputPath: 'output/test.pdf',
    );

    Bus::assertDispatched(RenderPdfJob::class);
});

it('respects queue config for retries and timeout', function () {
    $this->app['config']->set('pdf-studio.queue.timeout', 300);
    $this->app['config']->set('pdf-studio.queue.retries', 5);

    $job = new RenderPdfJob(
        view: 'simple',
        data: ['name' => 'Test'],
        outputPath: 'test.pdf',
    );

    expect($job->tries)->toBe(5)
        ->and($job->timeout)->toBe(300);
});
