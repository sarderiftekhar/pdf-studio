<?php

namespace PdfStudio\Laravel\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface UsageMeterContract
{
    public function recordRender(int $workspaceId, string $jobId, int $bytes, float $renderTimeMs): void;

    /** @return Collection<int, \PdfStudio\Laravel\Models\UsageRecord> */
    public function getUsage(int $workspaceId, Carbon $from, Carbon $to): Collection;

    /** @return array<string, int> */
    public function getSummary(int $workspaceId, Carbon $from, Carbon $to): array;
}
