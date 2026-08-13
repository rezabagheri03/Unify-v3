<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Resource;
use App\Observers\ResourceObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Resource::observe(ResourceObserver::class);

        // TODO-037 (PERF budgets): slow-query logging. Only queries at/above
        // the budget threshold (default 500 ms) are written to the 'perf'
        // channel — healthy traffic produces zero log volume. SQL text and
        // bindings are intentionally NOT logged (may contain PII/secrets);
        // the connection, timing and caller context are enough to bisect.
        if (config('unify.perf.enabled', true)) {
            DB::listen(function ($query) {
                if ($query->time >= (float) config('unify.perf.slow_query_ms', 500)) {
                    Log::channel('perf')->warning('slow query', [
                        'connection' => $query->connectionName,
                        'duration_ms' => $query->time,
                        'budget_ms' => (float) config('unify.perf.slow_query_ms', 500),
                        'fingerprint' => substr(md5($query->sql), 0, 12),
                    ]);
                }
            });
        }
    }
}