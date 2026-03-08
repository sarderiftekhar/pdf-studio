<?php

use PdfStudio\Laravel\Facades\Pdf;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['config']->set('pdf-studio.render_cache.enabled', true);
    $this->app['config']->set('pdf-studio.render_cache.ttl', 3600);
});

it('caches render output and returns cached on second call', function () {
    $result1 = Pdf::html('<h1>Test</h1>')->cache(3600)->render();
    $result2 = Pdf::html('<h1>Test</h1>')->cache(3600)->render();

    expect($result2->renderTimeMs)->toBe(0.0)
        ->and($result2->content())->toBe($result1->content());
});

it('noCache bypasses cache', function () {
    $result1 = Pdf::html('<h1>Test</h1>')->cache(3600)->render();
    $result2 = Pdf::html('<h1>Test</h1>')->cache(3600)->noCache()->render();

    expect($result2->renderTimeMs)->toBeGreaterThan(0);
});

it('different data produces different cache entries', function () {
    $result1 = Pdf::html('<h1>A</h1>')->cache(3600)->render();
    $result2 = Pdf::html('<h1>B</h1>')->cache(3600)->render();

    expect($result1->content())->not->toBe($result2->content());
});

it('does not cache when cache not called', function () {
    $result1 = Pdf::html('<h1>Test</h1>')->render();
    $result2 = Pdf::html('<h1>Test</h1>')->render();

    expect($result1->renderTimeMs)->toBeGreaterThan(0)
        ->and($result2->renderTimeMs)->toBeGreaterThan(0);
});
