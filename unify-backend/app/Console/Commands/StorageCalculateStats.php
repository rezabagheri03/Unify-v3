<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Resource;
use App\Models\SystemConfig;

class StorageCalculateStats extends Command
{
    protected $signature = 'storage:calculate-stats';
    protected $description = 'Calculate current storage usage (50GB limit)';

    public function handle()
    {
        $used = Resource::where('is_deleted_content', false)->sum('file_size_bytes');
        
        SystemConfig::updateOrCreate(
            ['key' => 'storage_used_bytes'],
            ['value' => $used]
        );

        $this->info('Storage used: ' . round($used / 1024 / 1024 / 1024, 2) . ' GB');
    }
}