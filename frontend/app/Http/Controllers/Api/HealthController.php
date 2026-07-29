<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function check(Request $request)
    {
        $isIntranet = $this->detectIntranet();

        return response()->json([
            'status' => 'ok',
            'mode' => $isIntranet ? 'intranet' : 'online',
            'version' => '9.0.0',
            'timestamp' => now()->toIso8601String(),
            'intranet_detection' => 'Use /health to detect intranet mode'
        ]);
    }

    private function detectIntranet(): bool
    {
        // Simple detection: try to reach google.com
        try {
            $ch = curl_init('https://www.google.com');
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode !== 200;
        } catch (\Exception $e) {
            return true; // Assume intranet if error
        }
    }
}