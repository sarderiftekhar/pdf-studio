<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PdfStudio\Laravel\Events\RenderCompleted;
use PdfStudio\Laravel\Events\RenderFailed;
use PdfStudio\Laravel\Events\RenderStarting;
use PdfStudio\Laravel\Listeners\RenderLogger;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['config']->set('pdf-studio.logging.enabled', true);
    $this->app['config']->set('pdf-studio.logging.channel', null);
});

it('listens for render events when logging is enabled', function () {
    // Re-boot to register listeners
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $hasStartingListener = Event::hasListeners(RenderStarting::class);
    $hasCompletedListener = Event::hasListeners(RenderCompleted::class);
    $hasFailedListener = Event::hasListeners(RenderFailed::class);

    expect($hasStartingListener)->toBeTrue()
        ->and($hasCompletedListener)->toBeTrue()
        ->and($hasFailedListener)->toBeTrue();
});

it('does not register listeners when logging is disabled', function () {
    $this->app['config']->set('pdf-studio.logging.enabled', false);

    // Fresh event dispatcher
    $this->app->instance('events', new \Illuminate\Events\Dispatcher($this->app));

    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $hasStartingListener = Event::hasListeners(RenderStarting::class);

    expect($hasStartingListener)->toBeFalse();
});

it('logs render completion with context', function () {
    $logger = new RenderLogger($this->app);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'PDF render completed')
                && $context['driver'] === 'fake'
                && $context['render_time_ms'] === 42.5
                && $context['bytes'] === 100;
        });

    $logger->handleCompleted(new RenderCompleted(
        driver: 'fake',
        renderTimeMs: 42.5,
        bytes: 100,
    ));
});
