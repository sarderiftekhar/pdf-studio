<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use PdfStudio\Laravel\Models\ApiKey;
use PdfStudio\Laravel\Models\RenderJob;
use PdfStudio\Laravel\Models\Workspace;

uses(RefreshDatabase::class);

function apiHeaders(string $key): array
{
    return ['Authorization' => "Bearer {$key}"];
}

function setupSaasWorkspace(): array
{
    config([
        'pdf-studio.saas.enabled' => true,
        'pdf-studio.saas.api.middleware' => [],
        'pdf-studio.default_driver' => 'fake',
    ]);

    // Re-register routes
    $provider = app()->getProvider(\PdfStudio\Laravel\PdfStudioServiceProvider::class);
    $provider->boot();

    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    $generated = ApiKey::generate();
    ApiKey::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Key',
        'key' => hash('sha256', $generated['key']),
        'prefix' => $generated['prefix'],
    ]);

    return ['workspace' => $workspace, 'raw_key' => $generated['key']];
}

it('renders PDF synchronously via API', function () {
    $setup = setupSaasWorkspace();

    $response = $this->postJson('/api/pdf-studio/render', [
        'html' => '<h1>Hello API</h1>',
    ], apiHeaders($setup['raw_key']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('creates async render job', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $setup = setupSaasWorkspace();

    $response = $this->postJson('/api/pdf-studio/render/async', [
        'html' => '<h1>Async</h1>',
        'output_path' => 'output/test.pdf',
    ], apiHeaders($setup['raw_key']));

    $response->assertStatus(202)
        ->assertJsonStructure(['id', 'status']);

    expect($response->json('status'))->toBe('pending');
});

it('returns render job status', function () {
    $setup = setupSaasWorkspace();

    $job = RenderJob::create([
        'workspace_id' => $setup['workspace']->id,
        'status' => 'completed',
        'html' => '<h1>Done</h1>',
        'bytes' => 1024,
        'render_time_ms' => 150.5,
        'completed_at' => now(),
    ]);

    $response = $this->getJson(
        "/api/pdf-studio/render/{$job->id}",
        apiHeaders($setup['raw_key'])
    );

    $response->assertOk()
        ->assertJson([
            'id' => $job->id,
            'status' => 'completed',
            'bytes' => 1024,
        ]);
});

it('returns 404 for job from different workspace', function () {
    $setup = setupSaasWorkspace();
    $other = Workspace::create(['name' => 'Other', 'slug' => 'other']);

    $job = RenderJob::create([
        'workspace_id' => $other->id,
        'status' => 'completed',
    ]);

    $response = $this->getJson(
        "/api/pdf-studio/render/{$job->id}",
        apiHeaders($setup['raw_key'])
    );

    $response->assertStatus(404);
});

it('rejects unauthenticated requests', function () {
    config([
        'pdf-studio.saas.enabled' => true,
        'pdf-studio.saas.api.middleware' => [],
    ]);

    $provider = app()->getProvider(\PdfStudio\Laravel\PdfStudioServiceProvider::class);
    $provider->boot();

    $response = $this->postJson('/api/pdf-studio/render', [
        'html' => '<h1>No Auth</h1>',
    ]);

    $response->assertStatus(401);
});
