<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use PdfStudio\Laravel\Models\Workspace;
use PdfStudio\Laravel\Models\WorkspaceMember;
use PdfStudio\Laravel\Services\AccessControl;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AccessControl;
});

it('checks if user can access workspace', function () {
    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 1, 'role' => 'member']);

    expect($this->service->canAccess($workspace, 1))->toBeTrue()
        ->and($this->service->canAccess($workspace, 999))->toBeFalse();
});

it('checks role-based permissions', function () {
    $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 1, 'role' => 'owner']);
    WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 2, 'role' => 'viewer']);

    expect($this->service->canManage($workspace, 1))->toBeTrue()
        ->and($this->service->canManage($workspace, 2))->toBeFalse();
});

it('resolves workspace by slug', function () {
    Workspace::create(['name' => 'Acme', 'slug' => 'acme']);

    $workspace = $this->service->resolveWorkspace('acme');

    expect($workspace)->toBeInstanceOf(Workspace::class)
        ->and($workspace->name)->toBe('Acme');
});

it('returns null for non-existent workspace slug', function () {
    expect($this->service->resolveWorkspace('missing'))->toBeNull();
});
