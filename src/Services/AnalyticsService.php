<?php

namespace PdfStudio\Laravel\Services;

use Illuminate\Support\Carbon;
use PdfStudio\Laravel\Contracts\AnalyticsServiceContract;
use PdfStudio\Laravel\Models\RenderJob;

class AnalyticsService implements AnalyticsServiceContract
{
    /** @return array{total: int, completed: int, failed: int, avg_render_time_ms: float, total_bytes: int} */
    public function getStats(int $workspaceId, Carbon $from, Carbon $to): array
    {
        $query = RenderJob::where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to]);

        $total = (clone $query)->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $failed = (clone $query)->where('status', 'failed')->count();

        $avgRenderTime = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('render_time_ms')
            ->avg('render_time_ms');

        $totalBytes = (clone $query)
            ->where('status', 'completed')
            ->sum('bytes');

        return [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'avg_render_time_ms' => round((float) ($avgRenderTime ?? 0), 1),
            'total_bytes' => (int) $totalBytes,
        ];
    }
}
