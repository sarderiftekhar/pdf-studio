<?php

use PdfStudio\Laravel\Cache\RenderCache;
use PdfStudio\Laravel\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->app['config']->set('pdf-studio.render_cache.enabled', true);
    $this->app['config']->set('pdf-studio.render_cache.ttl', 3600);
});

it('generates deterministic cache keys', function () {
    $cache = app(RenderCache::class);

    $key1 = $cache->key('invoice', ['id' => 1], ['format' => 'A4'], 'fake');
    $key2 = $cache->key('invoice', ['id' => 1], ['format' => 'A4'], 'fake');

    expect($key1)->toBe($key2)
        ->and($key1)->toStartWith('pdf-studio:render:');
});

it('generates different keys for different data', function () {
    $cache = app(RenderCache::class);

    $key1 = $cache->key('invoice', ['id' => 1], ['format' => 'A4'], 'fake');
    $key2 = $cache->key('invoice', ['id' => 2], ['format' => 'A4'], 'fake');

    expect($key1)->not->toBe($key2);
});

it('stores and retrieves cached content', function () {
    $cache = app(RenderCache::class);

    $key = $cache->key('test', [], [], 'fake');
    $cache->put($key, 'PDF_CONTENT');

    expect($cache->get($key))->toBe('PDF_CONTENT');
});

it('returns null for missing keys', function () {
    $cache = app(RenderCache::class);

    expect($cache->get('pdf-studio:render:nonexistent'))->toBeNull();
});

it('flushes only render cache keys', function () {
    $cache = app(RenderCache::class);

    $key = $cache->key('test', [], [], 'fake');
    $cache->put($key, 'PDF_CONTENT');

    $cache->flush();

    expect($cache->get($key))->toBeNull();
});

it('returns null when disabled', function () {
    $this->app['config']->set('pdf-studio.render_cache.enabled', false);
    $cache = new RenderCache($this->app);

    $key = $cache->key('test', [], [], 'fake');
    $cache->put($key, 'PDF_CONTENT');

    expect($cache->get($key))->toBeNull();
});
