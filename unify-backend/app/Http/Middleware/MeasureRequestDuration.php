<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TODO-037 (PERF budgets): request-duration logging, logged ONLY when a
 * request exceeds the configured budget (default 800 ms — the notification
 * poll budget). Zero per-request log noise under healthy operation; the
 * 'perf' channel aggregates violations for the staging/production budget
 * reviews in docs/PERF_BUDGETS.md. Logging happens in terminate() so the
 * measurement never delays the response.
 */
class MeasureRequestDuration
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        $threshold = (float) config('unify.perf.request_ms', 800);
        $ms = (microtime(true) - (float) $request->server->get('REQUEST_TIME_FLOAT')) * 1000;

        if ($ms >= $threshold) {
            Log::channel('perf')->warning('slow request', [
                'method' => $request->method(),
                'path' => '/' . ltrim($request->path(), '/'),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($ms, 1),
                'budget_ms' => $threshold,
                'user' => $request->user()?->id,
            ]);
        }
    }
}
