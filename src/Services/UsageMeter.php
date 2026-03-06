<?php

namespace PdfStudio\Laravel\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PdfStudio\Laravel\Contracts\UsageMeterContract;
use PdfStudio\Laravel\Events\BillableEvent;
use PdfStudio\Laravel\Models\UsageRecord;

class UsageMeter implements UsageMeterContract
{
    public function recordRender(int $workspaceId, string $jobId, int $bytes, float $renderTimeMs): void
    {
        $idempotencyKey = "render:{$jobId}";

        $exists = UsageRecord::where('idempotency_key', $idempotencyKey)->exists();

        if ($exists) {
            return;
        }

        UsageRecord::create([
            'workspace_id' => $workspaceId,
            'event_type' => 'render',
            'idempotency_key' => $idempotencyKey,
            'quantity' => 1,
            'metadata' => [
                'bytes' => $bytes,
                'render_time_ms' => $renderTimeMs,
            ],
        ]);

        event(new BillableEvent(
            workspaceId: $workspaceId,
            eventType: 'render',
            quantity: 1,
            metadata: ['bytes' => $bytes, 'render_time_ms' => $renderTimeMs],
        ));
    }

    /** @return Collection<int, UsageRecord> */
    public function getUsage(int $workspaceId, Carbon $from, Carbon $to): Collection
    {
        /** @var Collection<int, UsageRecord> */
        return UsageRecord::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();
    }

    /** @return array<string, int> */
    public function getSummary(int $workspaceId, Carbon $from, Carbon $to): array
    {
        $records = UsageRecord::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('event_type, SUM(quantity) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        return $records->map(fn ($val) => (int) $val)->toArray();
    }
}
