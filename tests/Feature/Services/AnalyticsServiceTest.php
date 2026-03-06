<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use PdfStudio\Laravel\Models\RenderJob;
use PdfStudio\Laravel\Models\Workspace;
use PdfStudio\Laravel\Services\AnalyticsService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    $this->analytics = new AnalyticsService;
});

it('returns render volume for a workspace', function () {
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'bytes' => 1024, 'completed_at' => now()]);
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'bytes' => 2048, 'completed_at' => now()]);
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'failed', 'error' => 'timeout', 'completed_at' => now()]);

    $stats = $this->analytics->getStats($this->workspace->id, now()->subHour(), now()->addHour());

    expect($stats['total'])->toBe(3)
        ->and($stats['completed'])->toBe(2)
        ->and($stats['failed'])->toBe(1);
});

it('calculates average render time', function () {
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'render_time_ms' => 100, 'completed_at' => now()]);
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'render_time_ms' => 200, 'completed_at' => now()]);

    $stats = $this->analytics->getStats($this->workspace->id, now()->subHour(), now()->addHour());

    expect($stats['avg_render_time_ms'])->toBe(150.0);
});

it('returns total bytes rendered', function () {
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'bytes' => 1024, 'completed_at' => now()]);
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'bytes' => 2048, 'completed_at' => now()]);

    $stats = $this->analytics->getStats($this->workspace->id, now()->subHour(), now()->addHour());

    expect($stats['total_bytes'])->toBe(3072);
});

it('filters by date range', function () {
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'created_at' => now()->subDays(5)]);
    RenderJob::create(['workspace_id' => $this->workspace->id, 'status' => 'completed', 'created_at' => now()]);

    $stats = $this->analytics->getStats($this->workspace->id, now()->subDay(), now()->addHour());

    expect($stats['total'])->toBe(1);
});

it('returns empty stats when no data', function () {
    $stats = $this->analytics->getStats($this->workspace->id, now()->subHour(), now()->addHour());

    expect($stats['total'])->toBe(0)
        ->and($stats['completed'])->toBe(0)
        ->and($stats['failed'])->toBe(0)
        ->and($stats['avg_render_time_ms'])->toBe(0.0)
        ->and($stats['total_bytes'])->toBe(0);
});
