<?php

namespace PdfStudio\Laravel\Services;

use PdfStudio\Laravel\Contracts\AccessControlContract;
use PdfStudio\Laravel\Models\Workspace;

class AccessControl implements AccessControlContract
{
    /** @var array<int, string> */
    protected array $manageRoles = ['owner', 'admin'];

    public function canAccess(Workspace $workspace, int $userId): bool
    {
        return $workspace->hasMember($userId);
    }

    public function canManage(Workspace $workspace, int $userId): bool
    {
        $role = $workspace->memberRole($userId);

        return $role !== null && in_array($role, $this->manageRoles, true);
    }

    public function resolveWorkspace(string $slug): ?Workspace
    {
        return Workspace::where('slug', $slug)->first();
    }
}
