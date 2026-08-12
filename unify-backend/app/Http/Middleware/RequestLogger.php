<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * One-line audit of every /api/v1 request: who (or who-not), what, and the
 * outcome. Lets us see exactly what the browser sent and why it failed.
 */
class RequestLogger
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = $request->user();
        Log::channel('auth')->info('REQ', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'has_bearer' => $request->bearerToken() !== null,
            'user' => $user?->id ?? null,
            'ip' => $request->ip(),
            'time' => now()->toIso8601String(),
        ]);

        return $response;
    }
}
