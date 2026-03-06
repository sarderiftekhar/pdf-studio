<?php

namespace PdfStudio\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $workspace_id
 * @property string $name
 * @property string $key
 * @property string $prefix
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<ApiKey>
 */
class ApiKey extends Model
{
    protected $table = 'pdf_studio_api_keys';

    protected $fillable = [
        'workspace_id',
        'name',
        'key',
        'prefix',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['key'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    /** @return array{key: string, prefix: string} */
    public static function generate(): array
    {
        $key = Str::random(64);

        return [
            'key' => $key,
            'prefix' => substr($key, 0, 8),
        ];
    }
}
