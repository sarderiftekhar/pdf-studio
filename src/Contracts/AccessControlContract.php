<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\Models\Workspace;

interface AccessControlContract
{
    public function canAccess(Workspace $workspace, int $userId): bool;

    public function canManage(Workspace $workspace, int $userId): bool;

    public function resolveWorkspace(string $slug): ?Workspace;
}
