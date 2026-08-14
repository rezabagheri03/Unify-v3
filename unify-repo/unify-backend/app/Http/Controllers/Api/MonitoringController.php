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
        $isIntranet = \App\Services\IntranetDetector::isIntranet();

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

}
