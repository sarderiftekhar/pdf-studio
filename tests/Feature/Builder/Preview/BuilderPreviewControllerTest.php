<?php

beforeEach(function () {
    $this->app['config']->set('pdf-studio.preview.enabled', true);
    $this->app['config']->set('pdf-studio.preview.environment_gate', false);
    $this->app['config']->set('pdf-studio.preview.middleware', []);

    // Register builder preview routes since they're registered at boot time
    // and we changed config after boot
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();
});

it('renders HTML preview from JSON schema', function () {
    $schema = [
        'version' => '1.0',
        'blocks' => [
            ['type' => 'text', 'content' => 'Hello World', 'tag' => 'h1', 'classes' => 'text-2xl'],
        ],
        'style_tokens' => [
            'primary_color' => '#000000',
            'font_family' => 'sans-serif',
            'font_size' => '16px',
            'line_height' => '1.5',
        ],
    ];

    $response = $this->postJson('/pdf-studio/builder/preview', [
        'schema' => $schema,
        'format' => 'html',
    ]);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    expect($response->getContent())->toContain('Hello World');
});

it('returns validation error for invalid schema', function () {
    $response = $this->postJson('/pdf-studio/builder/preview', [
        'schema' => ['version' => '1.0', 'blocks' => []],
        'format' => 'html',
    ]);

    $response->assertStatus(422);
});

it('returns validation error for missing schema', function () {
    $response = $this->postJson('/pdf-studio/builder/preview', [
        'format' => 'html',
    ]);

    $response->assertStatus(422);
});

it('returns PDF preview when format is pdf', function () {
    config(['pdf-studio.default_driver' => 'fake']);

    $schema = [
        'version' => '1.0',
        'blocks' => [
            ['type' => 'text', 'content' => 'PDF Test', 'tag' => 'p', 'classes' => ''],
        ],
        'style_tokens' => [
            'primary_color' => '#000000',
            'font_family' => 'sans-serif',
            'font_size' => '16px',
            'line_height' => '1.5',
        ],
    ];

    $response = $this->postJson('/pdf-studio/builder/preview', [
        'schema' => $schema,
        'format' => 'pdf',
    ]);

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('defaults to html format', function () {
    $schema = [
        'version' => '1.0',
        'blocks' => [
            ['type' => 'text', 'content' => 'Default format', 'tag' => 'p', 'classes' => ''],
        ],
        'style_tokens' => [
            'primary_color' => '#000000',
            'font_family' => 'sans-serif',
            'font_size' => '16px',
            'line_height' => '1.5',
        ],
    ];

    $response = $this->postJson('/pdf-studio/builder/preview', [
        'schema' => $schema,
    ]);

    $response->assertOk();
    expect($response->getContent())->toContain('Default format');
});
