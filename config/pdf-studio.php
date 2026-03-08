<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Renderer Driver
    |--------------------------------------------------------------------------
    |
    | The default driver used for PDF rendering. Supported: "chromium",
    | "wkhtmltopdf", "dompdf".
    |
    */
    'default_driver' => env('PDF_STUDIO_DRIVER', 'chromium'),

    /*
    |--------------------------------------------------------------------------
    | Renderer Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration for each supported rendering engine.
    |
    */
    'drivers' => [
        'chromium' => [
            'binary' => null,
            'node_binary' => null,
            'npm_binary' => null,
            'timeout' => 60,
            'options' => [],
        ],
        'wkhtmltopdf' => [
            'binary' => '/usr/local/bin/wkhtmltopdf',
            'timeout' => 60,
        ],
        'dompdf' => [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'options' => [],
        ],
        'fake' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tailwind CSS
    |--------------------------------------------------------------------------
    |
    | Configuration for the Tailwind CSS compilation pipeline.
    |
    */
    'tailwind' => [
        'binary' => null,
        'config' => null,
        'cache' => [
            'enabled' => true,
            'store' => null,
            'ttl' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    |
    | Preview route configuration. Disabled by default.
    |
    */
    'preview' => [
        'enabled' => env('PDF_STUDIO_PREVIEW', false),
        'prefix' => 'pdf-studio/preview',
        'middleware' => ['web', 'auth'],
        'data_providers' => [],
        'environment_gate' => true,
        'allowed_environments' => ['local', 'staging', 'testing'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | When enabled, renders dump HTML/CSS artifacts and timing to storage.
    |
    */
    'debug' => env('PDF_STUDIO_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Structured logging for render lifecycle events.
    |
    */
    'logging' => [
        'enabled' => env('PDF_STUDIO_LOGGING', false),
        'channel' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Register named PDF templates. Each template defines a Blade view,
    | optional default render options, and an optional data provider class.
    |
    */
    'templates' => [],

    /*
    |--------------------------------------------------------------------------
    | Starter Templates
    |--------------------------------------------------------------------------
    |
    | When enabled, registers the built-in starter templates (invoice, report,
    | certificate) that can be used as starting points.
    |
    */
    'starter_templates' => false,

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Default output and storage settings.
    |
    */
    'output' => [
        'default_disk' => null,
        'overwrite' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Queue settings for async PDF generation.
    |
    */
    'queue' => [
        'connection' => null,
        'queue' => 'default',
        'timeout' => 120,
        'retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Manipulation
    |--------------------------------------------------------------------------
    |
    | Configuration for PDF manipulation features (merge, watermark, protect,
    | AcroForm fill). Some features require pdftk installed on the system.
    |
    */
    'manipulation' => [
        'pdftk_binary' => env('PDF_STUDIO_PDFTK_BINARY', '/usr/bin/pdftk'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Render Caching
    |--------------------------------------------------------------------------
    |
    | Cache rendered PDF output to avoid re-rendering identical content.
    | Use ->cache(ttl) on PdfBuilder to enable per-call caching.
    |
    */
    'render_cache' => [
        'enabled' => env('PDF_STUDIO_RENDER_CACHE', false),
        'store' => null,
        'ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pro Features
    |--------------------------------------------------------------------------
    |
    | Enable Pro-tier features. Requires running migrations first:
    | php artisan vendor:publish --tag=pdf-studio-migrations
    | php artisan migrate
    |
    */
    'pro' => [
        'enabled' => env('PDF_STUDIO_PRO', false),
        'versioning' => [
            'enabled' => true,
        ],
        'workspaces' => [
            'enabled' => true,
            'user_model' => 'App\\Models\\User',
            'default_role' => 'member',
            'roles' => ['owner', 'admin', 'member', 'viewer'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SaaS Features
    |--------------------------------------------------------------------------
    |
    | Enable SaaS-tier features for hosted rendering platform.
    |
    */
    'saas' => [
        'enabled' => env('PDF_STUDIO_SAAS', false),
        'api' => [
            'prefix' => 'api/pdf-studio',
            'middleware' => ['api'],
            'rate_limit' => 60,
            // Restrict which Blade views may be rendered via the API.
            // Leave empty [] to allow only registered template views.
            // Set to ['pdf.invoice', 'pdf.report'] to allow specific views.
            'allowed_views' => [],
        ],
        'metering' => [
            'enabled' => true,
        ],
    ],

];
