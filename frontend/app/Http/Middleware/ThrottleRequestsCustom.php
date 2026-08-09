<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThrottleRequestsCustom
{
    public function handle(Request $request, Closure $next, $maxAttempts = 5, $decayMinutes = 15)
    {
        $key = 'throttle:' . $request->ip() . ':' . $request->path();

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'message' => 'تعداد درخواست زیاد - ' . $decayMinutes . ' دقیقه صبر کنید'
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        return $next($request);
    }
}