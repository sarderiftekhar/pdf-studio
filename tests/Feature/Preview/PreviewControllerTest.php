<?php

use PdfStudio\Laravel\Contracts\PreviewDataProviderContract;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['config']->set('pdf-studio.preview.enabled', true);
    $this->app['config']->set('pdf-studio.preview.middleware', []);

    // Register a default data provider for the simple template so it doesn't
    // throw an undefined variable error ($name is required by the stub view).
    $this->app['config']->set('pdf-studio.preview.data_providers', [
        'simple' => DefaultSimpleDataProvider::class,
    ]);

    // Register preview routes since they're registered at boot time
    // and we changed config after boot
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();
});

it('returns 404 when preview is disabled', function () {
    $this->app['config']->set('pdf-studio.preview.enabled', false);

    // The routes are already registered from beforeEach, so this test
    // verifies that templates that don't exist return 404
    $response = $this->get('/pdf-studio/preview/nonexistent');

    expect($response->getStatusCode())->toBe(404);
});

it('renders HTML preview of a blade template', function () {
    // Register test views
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $response = $this->get('/pdf-studio/preview/simple?format=html');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

it('renders PDF preview of a blade template', function () {
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $response = $this->get('/pdf-studio/preview/simple?format=pdf');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('defaults to HTML format when no format specified', function () {
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $response = $this->get('/pdf-studio/preview/simple');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

it('uses data provider when registered', function () {
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $this->app['config']->set('pdf-studio.preview.data_providers', [
        'simple' => TestSimpleDataProvider::class,
    ]);

    $response = $this->get('/pdf-studio/preview/simple?format=html');

    $response->assertOk();
    expect($response->getContent())->toContain('Provider Name');
});

it('returns 404 for non-existent templates', function () {
    $response = $this->get('/pdf-studio/preview/nonexistent');

    $response->assertNotFound();
});

// Default data provider used by most tests
class DefaultSimpleDataProvider implements PreviewDataProviderContract
{
    /** @return array<string, mixed> */
    public function data(): array
    {
        return ['name' => 'World'];
    }
}

// Test data provider for the specific data provider test
class TestSimpleDataProvider implements PreviewDataProviderContract
{
    /** @return array<string, mixed> */
    public function data(): array
    {
        return ['name' => 'Provider Name'];
    }
}
