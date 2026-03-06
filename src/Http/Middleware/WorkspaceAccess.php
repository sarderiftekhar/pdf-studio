<?php

namespace PdfStudio\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PdfStudio\Laravel\Contracts\AccessControlContract;

class WorkspaceAccess
{
    public function __construct(
        protected AccessControlContract $accessControl,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $slug = $request->route('workspace');
        $workspace = $this->accessControl->resolveWorkspace($slug);

        if ($workspace === null) {
            abort(404, 'Workspace not found.');
        }

        $userId = $request->user()?->id;

        if ($userId === null || !$this->accessControl->canAccess($workspace, (int) $userId)) {
            abort(403, 'You do not have access to this workspace.');
        }

        $request->attributes->set('workspace', $workspace);

        return $next($request);
    }
}
