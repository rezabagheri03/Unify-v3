<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class FileCacheService
{
    public function remember(string $key, int $seconds, callable $callback)
    {
        return Cache::remember($key, $seconds, $callback);
    }
}