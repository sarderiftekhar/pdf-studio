<?php

use PdfStudio\Laravel\Contracts\PreviewDataProviderContract;

beforeEach(function () {
    $this->app['config']->set('pdf-studio.default_driver', 'fake');
    $this->app['config']->set('pdf-studio.preview.enabled', true);
    $this->app['config']->set('pdf-studio.preview.middleware', []);
    $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

    $this->app['config']->set('pdf-studio.preview.data_providers', [
        'simple' => SecurityTestDataProvider::class,
    ]);
});

it('blocks preview in production when environment gate is enabled', function () {
    $this->app['config']->set('pdf-studio.preview.environment_gate', true);
    $this->app->detectEnvironment(fn () => 'production');

    // Re-boot to re-register routes with new config
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $response = $this->get('/pdf-studio/preview/simple?format=html');

    expect($response->getStatusCode())->toBe(404);
});

it('allows preview in local environment when gate is enabled', function () {
    $this->app['config']->set('pdf-studio.preview.environment_gate', true);
    $this->app->detectEnvironment(fn () => 'local');

    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $response = $this->get('/pdf-studio/preview/simple?format=html');

    $response->assertOk();
});

it('validates format parameter', function () {
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $response = $this->get('/pdf-studio/preview/simple?format=invalid');

    expect($response->getStatusCode())->toBe(422);
});

it('accepts valid format parameters', function () {
    $provider = $this->app->make(\PdfStudio\Laravel\PdfStudioServiceProvider::class, ['app' => $this->app]);
    $provider->boot();

    $htmlResponse = $this->get('/pdf-studio/preview/simple?format=html');
    $pdfResponse = $this->get('/pdf-studio/preview/simple?format=pdf');

    $htmlResponse->assertOk();
    $pdfResponse->assertOk();
});

class SecurityTestDataProvider implements PreviewDataProviderContract
{
    /** @return array<string, mixed> */
    public function data(): array
    {
        return ['name' => 'World'];
    }
}
