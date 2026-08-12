<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BroadcastThrottle;
use Illuminate\Http\Request;

class BroadcastThrottleController extends Controller
{
    public function check(Request $request)
    {
        $user = $request->user();
        $specId = $request->specification_id;

        $throttle = BroadcastThrottle::where('specification_id', $specId)
            ->where('professor_id', $user->id)
            ->first();

        if ($throttle && $throttle->last_sent_at->diffInMinutes(now()) < 10) {
            return response()->json(['allowed' => false, 'retry_after' => 10 - $throttle->last_sent_at->diffInMinutes(now())]);
        }

        return response()->json(['allowed' => true]);
    }
}