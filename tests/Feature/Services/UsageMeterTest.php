<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PdfStudio\Laravel\Events\BillableEvent;
use PdfStudio\Laravel\Models\UsageRecord;
use PdfStudio\Laravel\Models\Workspace;
use PdfStudio\Laravel\Services\UsageMeter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
    $this->meter = new UsageMeter;
});

it('records a render event', function () {
    $this->meter->recordRender($this->workspace->id, 'job-123', 1024, 150.5);

    expect(UsageRecord::count())->toBe(1);

    $record = UsageRecord::first();
    expect($record->event_type)->toBe('render')
        ->and($record->workspace_id)->toBe($this->workspace->id)
        ->and($record->idempotency_key)->toBe('render:job-123');
});

it('is idempotent - rejects duplicate events', function () {
    $this->meter->recordRender($this->workspace->id, 'job-123', 1024, 150.5);
    $this->meter->recordRender($this->workspace->id, 'job-123', 1024, 150.5);

    expect(UsageRecord::count())->toBe(1);
});

it('emits BillableEvent', function () {
    Event::fake([BillableEvent::class]);

    $this->meter->recordRender($this->workspace->id, 'job-456', 2048, 200.0);

    Event::assertDispatched(BillableEvent::class, function ($event) {
        return $event->workspaceId === $this->workspace->id
            && $event->eventType === 'render';
    });
});

it('queries usage for a workspace within date range', function () {
    $this->meter->recordRender($this->workspace->id, 'job-1', 1024, 100);
    $this->meter->recordRender($this->workspace->id, 'job-2', 2048, 200);

    $usage = $this->meter->getUsage($this->workspace->id, now()->subHour(), now()->addHour());

    expect($usage)->toHaveCount(2);
});

it('summarizes usage by event type', function () {
    $this->meter->recordRender($this->workspace->id, 'job-1', 1024, 100);
    $this->meter->recordRender($this->workspace->id, 'job-2', 2048, 200);

    $summary = $this->meter->getSummary($this->workspace->id, now()->subHour(), now()->addHour());

    expect($summary)->toHaveKey('render')
        ->and($summary['render'])->toBe(2);
});
