<?php

namespace PdfStudio\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $workspace_id
 * @property string $event_type
 * @property string $idempotency_key
 * @property int $quantity
 * @property array<string, mixed>|null $metadata
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<UsageRecord>
 */
class UsageRecord extends Model
{
    protected $table = 'pdf_studio_usage_records';

    protected $fillable = [
        'workspace_id',
        'event_type',
        'idempotency_key',
        'quantity',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
        'quantity' => 'integer',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
