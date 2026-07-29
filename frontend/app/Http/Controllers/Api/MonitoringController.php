<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function health()
    {
        $isIntranet = $this->detectIntranet();

        return response()->json([
            'status' => 'ok',
            'mode' => $isIntranet ? 'intranet' : 'online',
            'version' => '9.0.0',
            'timestamp' => now()->toIso8601String(),
            'storage' => $this->getStorageUsage(),
        ]);
    }

    public function storage()
    {
        return response()->json($this->getStorageUsage());
    }

    private function getStorageUsage(): array
    {
        $used = Resource::where('is_deleted_content', false)->sum('file_size_bytes');
        $limit = 50 * 1024 * 1024 * 1024; // 50GB

        return [
            'used_bytes' => $used,
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'limit_gb' => 50,
            'percentage' => round(($used / $limit) * 100, 1),
        ];
    }

    private function detectIntranet(): bool
    {
        try {
            $ch = curl_init('https://www.google.com');
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode !== 200;
        } catch (\Exception $e) {
            return true;
        }
    }
}