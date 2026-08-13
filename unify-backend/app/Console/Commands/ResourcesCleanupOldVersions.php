<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

class ResourcesCleanupOldVersions extends Command
{
    protected $signature = 'resources:cleanup-old-versions';
    protected $description = 'Hard delete old superseded resource file content after 30 days';

    public function handle()
    {
        $oldResources = Resource::where('is_superseded', true)
            ->where('scheduled_hard_delete_at', '<=', now())
            ->whereNotNull('file_path')
            ->get();

        foreach ($oldResources as $res) {
            if ($res->file_path && Storage::disk('public')->exists($res->file_path)) {
                Storage::disk('public')->delete($res->file_path);
            }
            $res->update([
                'file_path' => null,
                'is_deleted_content' => true,
            ]);
        }

        $this->info(count($oldResources) . ' old resource versions cleaned.');
    }
}