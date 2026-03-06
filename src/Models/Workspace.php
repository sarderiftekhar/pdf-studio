<?php

namespace PdfStudio\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Workspace>
 */
class Workspace extends Model
{
    protected $table = 'pdf_studio_workspaces';

    protected $fillable = ['name', 'slug'];

    /** @return HasMany<WorkspaceMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function memberRole(int $userId): ?string
    {
        $member = $this->members()->where('user_id', $userId)->first();

        return $member?->role;
    }
}
