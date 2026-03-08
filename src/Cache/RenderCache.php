<?php

namespace PdfStudio\Laravel\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;

class RenderCache
{
    protected Repository $store;

    protected bool $enabled;

    protected ?int $defaultTtl;

    private const REGISTRY_KEY = 'pdf-studio:render:_registry';

    public function __construct(Application $app)
    {
        /** @var string|null $storeName */
        $storeName = $app['config']->get('pdf-studio.render_cache.store');

        $this->store = $app['cache']->store($storeName);
        $this->enabled = (bool) $app['config']->get('pdf-studio.render_cache.enabled', false);

        /** @var int|null $ttl */
        $ttl = $app['config']->get('pdf-studio.render_cache.ttl');
        $this->defaultTtl = $ttl;
    }

    /**
     * Generate a cache key from render parameters.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $optionsArray
     */
    public function key(string $identifier, array $data, array $optionsArray, string $driver): string
    {
        $payload = json_encode(compact('identifier', 'data', 'optionsArray', 'driver'));

        return 'pdf-studio:render:'.hash('sha256', (string) $payload);
    }

    public function get(string $key): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        /** @var string|null */
        return $this->store->get($key);
    }

    public function put(string $key, string $content, ?int $ttl = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $ttl = $ttl ?? $this->defaultTtl;

        if ($ttl !== null) {
            $this->store->put($key, $content, $ttl);
        } else {
            $this->store->forever($key, $content);
        }

        /** @var array<string, bool> $registry */
        $registry = $this->store->get(self::REGISTRY_KEY, []);
        $registry[$key] = true;
        $this->store->forever(self::REGISTRY_KEY, $registry);
    }

    public function flush(): void
    {
        /** @var array<string, bool> $registry */
        $registry = $this->store->get(self::REGISTRY_KEY, []);

        foreach (array_keys($registry) as $key) {
            $this->store->forget($key);
        }

        $this->store->forget(self::REGISTRY_KEY);
    }
}
