<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Post-audit fix F-01: must_change_password is enforced SERVER-SIDE.
 *
 * Before this middleware the flag only drove a frontend redirect — a temp
 * password (printed envelope flow, F01) yielded a fully-capable 7-day token
 * with zero obligation to rotate. Now a flagged account may only reach the
 * rotation flow itself (+ /users/me so the SPA can render, and logout).
 */
class EnsurePasswordChanged
{
    /** Endpoints reachable while must_change_password = true (relative to /api). */
    private const ALLOWED = [
        'api/v1/onboarding',
        'api/v1/password/change',
        'api/v1/auth/logout',
        'api/v1/users/me',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->is(self::ALLOWED)) {
            return response()->json([
                'message' => 'ابتدا رمز موقت خود را تغییر دهید',
                'code' => 'PASSWORD_CHANGE_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
