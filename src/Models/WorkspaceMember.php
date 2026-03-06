<?php

namespace PdfStudio\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $workspace_id
 * @property int $user_id
 * @property string $role
 */
class WorkspaceMember extends Model
{
    protected $table = 'pdf_studio_workspace_members';

    protected $fillable = ['workspace_id', 'user_id', 'role'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
