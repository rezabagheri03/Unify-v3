<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        // PERF-15 fix: read the stat row written daily by storage:calculate-stats
        // instead of running a full-table SUM on every monitoring call. Falls
        // back to the live SUM once if the stat row has never been written.
        $used = SystemConfig::where('key', 'storage_used_bytes')->value('value');
        if ($used === null) {
            $used = Resource::where('is_deleted_content', false)->sum('file_size_bytes');
        }
        $used = (int) $used;
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
        // PERF-03 fix: cached 60s instead of a live 3s-timeout curl per request.
        return (bool) Cache::remember('health:intranet_mode', 60, function () {
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
        });
    }
}
