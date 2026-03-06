<?php

namespace PdfStudio\Laravel\Contracts;

use Illuminate\Support\Carbon;

interface AnalyticsServiceContract
{
    /** @return array{total: int, completed: int, failed: int, avg_render_time_ms: float, total_bytes: int} */
    public function getStats(int $workspaceId, Carbon $from, Carbon $to): array;
}
