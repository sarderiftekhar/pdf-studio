<?php

namespace PdfStudio\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PdfStudio\Laravel\Models\ApiKey;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();

        if ($token === null) {
            abort(401, 'API key required.');
        }

        $hashedKey = hash('sha256', $token);
        $apiKey = ApiKey::where('key', $hashedKey)->first();

        if ($apiKey === null || ! $apiKey->isActive()) {
            abort(401, 'Invalid or inactive API key.');
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('workspace', $apiKey->workspace);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
