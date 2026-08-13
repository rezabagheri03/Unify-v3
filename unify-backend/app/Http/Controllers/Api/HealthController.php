<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function check(Request $request)
    {
        $isIntranet = \App\Services\IntranetDetector::isIntranet();

        return response()->json([
            'status' => 'ok',
            'mode' => $isIntranet ? 'intranet' : 'online',
            'version' => '9.0.0',
            'timestamp' => now()->toIso8601String(),
            'intranet_detection' => 'Use /health to detect intranet mode'
        ]);
    }

}