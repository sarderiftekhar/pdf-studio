<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use PdfStudio\Laravel\Http\Middleware\WorkspaceAccess;
use PdfStudio\Laravel\Models\Workspace;
use PdfStudio\Laravel\Models\WorkspaceMember;
use PdfStudio\Laravel\Services\AccessControl;

uses(RefreshDatabase::class);

function createRequestWithRoute(string $slug, ?object $user = null): Request
{
    $request = Request::create("/workspace/{$slug}/test");

    $route = new Route('GET', '/workspace/{workspace}/test', fn () => 'ok');
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    if ($user !== null) {
        $request->setUserResolver(fn () => $user);
    }

    return $request;
}

it('allows access for workspace members', function () {
    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 1, 'role' => 'member']);

    $request = createRequestWithRoute('acme', (object) ['id' => 1]);

    $middleware = new WorkspaceAccess(new AccessControl);
    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('denies access for non-members with 403', function () {
    Workspace::create(['name' => 'Acme', 'slug' => 'acme']);

    $request = createRequestWithRoute('acme', (object) ['id' => 999]);

    $middleware = new WorkspaceAccess(new AccessControl);

    expect(fn () => $middleware->handle($request, fn () => new Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('returns 404 for non-existent workspace', function () {
    $request = createRequestWithRoute('nonexistent', (object) ['id' => 1]);

    $middleware = new WorkspaceAccess(new AccessControl);

    expect(fn () => $middleware->handle($request, fn () => new Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('denies access when no user is authenticated', function () {
    Workspace::create(['name' => 'Acme', 'slug' => 'acme']);

    $request = createRequestWithRoute('acme');

    $middleware = new WorkspaceAccess(new AccessControl);

    expect(fn () => $middleware->handle($request, fn () => new Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});
