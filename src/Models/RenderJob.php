<?php

namespace PdfStudio\Laravel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $workspace_id
 * @property string $status
 * @property string|null $view
 * @property string|null $html
 * @property array<string, mixed>|null $data
 * @property array<string, mixed>|null $options
 * @property string|null $driver
 * @property string|null $output_path
 * @property string|null $output_disk
 * @property int|null $bytes
 * @property float|null $render_time_ms
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $completed_at
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<RenderJob>
 */
class RenderJob extends Model
{
    use HasUuids;

    protected $table = 'pdf_studio_render_jobs';

    protected $fillable = [
        'workspace_id',
        'status',
        'view',
        'html',
        'data',
        'options',
        'driver',
        'output_path',
        'output_disk',
        'bytes',
        'render_time_ms',
        'error',
        'completed_at',
        'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'array',
        'options' => 'array',
        'bytes' => 'integer',
        'render_time_ms' => 'float',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function markCompleted(int $bytes, float $renderTimeMs, ?string $outputPath = null): void
    {
        $this->update([
            'status' => 'completed',
            'bytes' => $bytes,
            'render_time_ms' => $renderTimeMs,
            'output_path' => $outputPath,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);
    }
}
