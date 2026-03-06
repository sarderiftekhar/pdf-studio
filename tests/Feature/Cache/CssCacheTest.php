<?php

use Illuminate\Support\Facades\Cache;
use PdfStudio\Laravel\Cache\CssCache;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', true);
    $this->app['config']->set('pdf-studio.tailwind.cache.store', null);
    $this->app['config']->set('pdf-studio.tailwind.cache.ttl', null);
});

it('generates a deterministic cache key from html content', function () {
    $cache = app(CssCache::class);

    $key1 = $cache->key('<div class="text-red-500">Hello</div>');
    $key2 = $cache->key('<div class="text-red-500">Hello</div>');
    $key3 = $cache->key('<div class="text-blue-500">Hello</div>');

    expect($key1)->toBe($key2)
        ->and($key1)->not->toBe($key3)
        ->and($key1)->toStartWith('pdf-studio:css:');
});

it('returns cached CSS when available', function () {
    Cache::put('pdf-studio:css:testkey', '.cached { color: red; }');

    $cache = app(CssCache::class);
    $result = $cache->get('pdf-studio:css:testkey');

    expect($result)->toBe('.cached { color: red; }');
});

it('returns null when cache miss', function () {
    $cache = app(CssCache::class);
    $result = $cache->get('pdf-studio:css:nonexistent');

    expect($result)->toBeNull();
});

it('stores CSS in cache', function () {
    $cache = app(CssCache::class);
    $cache->put('pdf-studio:css:testkey', '.stored { color: blue; }');

    expect(Cache::get('pdf-studio:css:testkey'))->toBe('.stored { color: blue; }');
});

it('respects TTL from config', function () {
    $this->app['config']->set('pdf-studio.tailwind.cache.ttl', 3600);

    $cache = app(CssCache::class);
    $cache->put('pdf-studio:css:ttlkey', '.ttl { color: green; }');

    expect(Cache::get('pdf-studio:css:ttlkey'))->toBe('.ttl { color: green; }');
});

it('skips cache when disabled', function () {
    $this->app['config']->set('pdf-studio.tailwind.cache.enabled', false);

    $cache = app(CssCache::class);
    $cache->put('pdf-studio:css:disabledkey', '.disabled { color: red; }');

    expect(Cache::get('pdf-studio:css:disabledkey'))->toBeNull();
});

it('flushes all pdf-studio CSS cache entries', function () {
    Cache::put('pdf-studio:css:key1', '.a {}');
    Cache::put('pdf-studio:css:key2', '.b {}');

    $cache = app(CssCache::class);
    $cache->flush();

    expect($cache->get('pdf-studio:css:key1'))->toBeNull();
});
