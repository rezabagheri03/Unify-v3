<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Crypt;

class AuditLogMiddleware
{
    // Only log sensitive actions to avoid performance issues
    private array $sensitiveActions = [
        'DELETE', 'PATCH', 'PUT',
        'password', 'ban', 'role', 'honor', 'enrollment/final'
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $shouldLog = false;
        foreach ($this->sensitiveActions as $action) {
            if (str_contains($request->path(), $action) || $request->method() === 'DELETE') {
                $shouldLog = true;
                break;
            }
        }

        if ($shouldLog && $request->user()) {
            AuditLog::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $request->user()->id,
                'action' => $this->getActionType($request),
                'resource_type' => $request->route()->getName() ?? 'unknown',
                'resource_id' => $request->route('id') ?? 'bulk',
                'timestamp' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => Crypt::encryptString(json_encode($request->except(['password', 'password_hash']))),
            ]);
        }

        return $response;
    }

    private function getActionType(Request $request): string
    {
        if ($request->method() === 'DELETE') return 'deletion';
        if (str_contains($request->path(), 'password')) return 'password_reset';
        if (str_contains($request->path(), 'ban')) return 'ban';
        return 'major_edit';
    }
}