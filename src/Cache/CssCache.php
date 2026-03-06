<?php

namespace PdfStudio\Laravel\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;

class CssCache
{
    protected Repository $store;

    protected bool $enabled;

    protected ?int $ttl;

    public function __construct(Application $app)
    {
        /** @var string|null $storeName */
        $storeName = $app['config']->get('pdf-studio.tailwind.cache.store');

        $this->store = $app['cache']->store($storeName);
        $this->enabled = (bool) $app['config']->get('pdf-studio.tailwind.cache.enabled', true);

        /** @var int|null $ttl */
        $ttl = $app['config']->get('pdf-studio.tailwind.cache.ttl');
        $this->ttl = $ttl;
    }

    /**
     * Generate a cache key from HTML content.
     */
    public function key(string $html): string
    {
        return 'pdf-studio:css:'.hash('sha256', $html);
    }

    /**
     * Get cached CSS by key.
     */
    public function get(string $key): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        /** @var string|null */
        return $this->store->get($key);
    }

    /**
     * Store compiled CSS.
     */
    public function put(string $key, string $css): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->ttl !== null) {
            $this->store->put($key, $css, $this->ttl);
        } else {
            $this->store->forever($key, $css);
        }
    }

    /**
     * Flush all PDF Studio CSS cache entries.
     */
    public function flush(): void
    {
        $this->store->clear();
    }
}
