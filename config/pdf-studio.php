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
    | CSS Framework
    |--------------------------------------------------------------------------
    |
    | The default CSS framework used in the render pipeline.
    | Supported: "tailwind", "bootstrap", "none"
    |
    */
    'css_framework' => 'tailwind', // 'tailwind', 'bootstrap', 'none'

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
        'cloudflare' => [
            'account_id' => env('PDF_STUDIO_CLOUDFLARE_ACCOUNT_ID', ''),
            'api_token' => env('PDF_STUDIO_CLOUDFLARE_API_TOKEN', ''),
            'base_url' => env('PDF_STUDIO_CLOUDFLARE_BASE_URL', 'https://api.cloudflare.com/client/v4'),
            'timeout' => 30,
        ],
        'gotenberg' => [
            'url' => env('PDF_STUDIO_GOTENBERG_URL', 'http://127.0.0.1:3000'),
            'timeout' => 60,
            'headers' => [],
        ],
        'weasyprint' => [
            'binary' => env('PDF_STUDIO_WEASYPRINT_BINARY', 'weasyprint'),
            'timeout' => 60,
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
    | Fonts
    |--------------------------------------------------------------------------
    |
    | Register reusable font families and source files for diagnostics and
    | future driver integrations. Each entry should define a family name and
    | one or more local font file paths.
    |
    */
    'fonts' => [
        // 'inter' => [
        //     'family' => 'Inter',
        //     'sources' => [
        //         resource_path('fonts/Inter-Regular.ttf'),
        //     ],
        //     'weight' => 'normal',
        //     'style' => 'normal',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | Controls how local and remote assets referenced from HTML are handled
    | before rendering. Local images can be inlined automatically to reduce
    | driver-specific file path issues. Remote assets may be blocked entirely.
    |
    */
    'assets' => [
        'inline_local' => true,
        'allow_remote' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Barcode
    |--------------------------------------------------------------------------
    |
    | Default settings for barcode generation via the @barcode directive.
    |
    */
    'barcode' => [
        'default_type' => 'CODE128',
        'default_width' => 2,
        'default_height' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code
    |--------------------------------------------------------------------------
    |
    | Default settings for QR code generation via the @qrcode directive.
    |
    */
    'qrcode' => [
        'default_size' => 150,
        'error_correction' => 'M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail
    |--------------------------------------------------------------------------
    |
    | Settings for PDF thumbnail generation. Strategy can be 'auto',
    | 'imagick', or 'chromium'.
    |
    */
    'thumbnail' => [
        'strategy' => 'auto', // 'auto', 'imagick', 'chromium'
        'default_width' => 300,
        'default_format' => 'png',
        'quality' => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table of Contents
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic table of contents generation.
    |
    */
    'toc' => [
        'enabled' => false,
        'depth' => 6,
        'title' => 'Table of Contents',
        'mode' => 'auto',
        'view' => 'pdf-studio::toc',
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
