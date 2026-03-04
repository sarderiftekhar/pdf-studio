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

];
