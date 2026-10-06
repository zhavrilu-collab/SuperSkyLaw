<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAdminSyncApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('admin_sync.api_key');

        if (! is_string($configuredKey) || $configuredKey === '') {
            abort(503, 'Admin sync API nije konfiguriran.');
        }

        $providedKey = $request->bearerToken();

        if ($providedKey === null || ! hash_equals($configuredKey, $providedKey)) {
            abort(401, 'Neispravan API ključ.');
        }

        return $next($request);
    }
}
