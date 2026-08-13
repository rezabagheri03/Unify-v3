<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Shared intranet/egress detector (post-audit F-16: HealthController and
 * MonitoringController each carried their own copy of this probe).
 *
 * cached 60s so a 3s-timeout curl NEVER holds a PHP worker per request.
 */
class IntranetDetector
{
    public static function isIntranet(): bool
    {
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
                return true; // unreachable egress == treat as intranet
            }
        });
    }
}
