<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use PdfStudio\Laravel\Http\Middleware\ApiKeyAuth;
use PdfStudio\Laravel\Models\ApiKey;
use PdfStudio\Laravel\Models\Workspace;

uses(RefreshDatabase::class);

function createApiRequest(string $url, ?string $apiKey = null): Request
{
    $request = Request::create($url, 'POST');

    if ($apiKey !== null) {
        $request->headers->set('Authorization', "Bearer {$apiKey}");
    }

    $route = new Route('POST', $url, fn () => 'ok');
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

it('authenticates with valid API key', function () {
    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    $generated = ApiKey::generate();
    $apiKey = ApiKey::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Key',
        'key' => hash('sha256', $generated['key']),
        'prefix' => $generated['prefix'],
    ]);

    $request = createApiRequest('/api/test', $generated['key']);
    $middleware = new ApiKeyAuth;

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getContent())->toBe('OK')
        ->and($request->attributes->get('workspace'))->toBeInstanceOf(Workspace::class)
        ->and($request->attributes->get('api_key'))->toBeInstanceOf(ApiKey::class);
});

it('rejects request without API key', function () {
    $request = createApiRequest('/api/test');
    $middleware = new ApiKeyAuth;

    expect(fn () => $middleware->handle($request, fn () => new Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('rejects revoked API key', function () {
    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    $generated = ApiKey::generate();
    ApiKey::create([
        'workspace_id' => $workspace->id,
        'name' => 'Revoked Key',
        'key' => hash('sha256', $generated['key']),
        'prefix' => $generated['prefix'],
        'revoked_at' => now(),
    ]);

    $request = createApiRequest('/api/test', $generated['key']);
    $middleware = new ApiKeyAuth;

    expect(fn () => $middleware->handle($request, fn () => new Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('rejects expired API key', function () {
    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    $generated = ApiKey::generate();
    ApiKey::create([
        'workspace_id' => $workspace->id,
        'name' => 'Expired Key',
        'key' => hash('sha256', $generated['key']),
        'prefix' => $generated['prefix'],
        'expires_at' => now()->subDay(),
    ]);

    $request = createApiRequest('/api/test', $generated['key']);
    $middleware = new ApiKeyAuth;

    expect(fn () => $middleware->handle($request, fn () => new Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});
