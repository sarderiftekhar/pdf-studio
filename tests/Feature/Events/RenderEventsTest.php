<?php

use Illuminate\Support\Facades\Event;
use PdfStudio\Laravel\Events\RenderCompleted;
use PdfStudio\Laravel\Events\RenderFailed;
use PdfStudio\Laravel\Events\RenderStarting;
use PdfStudio\Laravel\Facades\Pdf;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
});

it('dispatches RenderStarting before rendering', function () {
    Event::fake([RenderStarting::class]);

    Pdf::html('<h1>Test</h1>')->render();

    Event::assertDispatched(RenderStarting::class, function ($event) {
        return $event->html === '<h1>Test</h1>' && $event->driver === 'fake';
    });
});

it('dispatches RenderCompleted after successful render', function () {
    Event::fake([RenderCompleted::class]);

    Pdf::html('<h1>Test</h1>')->render();

    Event::assertDispatched(RenderCompleted::class, function ($event) {
        return $event->driver === 'fake'
            && $event->renderTimeMs >= 0
            && $event->bytes > 0;
    });
});

it('dispatches RenderFailed on render error', function () {
    Event::fake([RenderFailed::class]);

    try {
        Pdf::render();
    } catch (\Throwable) {
        // Expected
    }

    Event::assertDispatched(RenderFailed::class);
});
