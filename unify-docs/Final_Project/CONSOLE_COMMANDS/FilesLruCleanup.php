<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

// FIX C4 & H2: LRU cleanup for 50GB Shop+40GB extra = 50GB total - truly evergreen but with fallback LRU if >80%
// Tracks last_downloaded_at for LRU order
class FilesLruCleanup extends Command
{
    protected $signature = 'files:lru-cleanup';
    protected $description = 'LRU cleanup to keep under 80% of 50GB limit (40GB) until 70% (35GB), protected professor files never deleted';

    public function handle()
    {
        $limitBytes = 50 * 1024 * 1024 * 1024; // 50GB total per user choice upgrade_50gb
        $thresholdBytes = 40 * 1024 * 1024 * 1024; // 80% = 40GB
        $targetBytes = 35 * 1024 * 1024 * 1024; // 70% = 35GB

        $totalBytes = Resource::where('is_deleted_content', false)->sum('file_size_bytes');
        
        if ($totalBytes < $thresholdBytes) {
            $this->info("Storage $totalBytes bytes under threshold $thresholdBytes, no cleanup needed");
            return;
        }

        $this->info("Storage $totalBytes over threshold $thresholdBytes, cleaning up to $targetBytes");

        // Get least recently downloaded non-protected resources
        $resources = Resource::where('is_deleted_content', false)
            ->where('is_protected', false)
            ->where('is_superseded', false) // Don't delete current versions, only old? Actually for LRU we delete oldest accessed
            ->orderBy('last_downloaded_at', 'asc')
            ->orderBy('created_at_g', 'asc')
            ->get();

        $deletedBytes = 0;
        foreach ($resources as $resource) {
            if ($totalBytes - $deletedBytes <= $targetBytes) break;
            
            // Delete file content
            if ($resource->file_path && Storage::disk('public')->exists(str_replace('/uploads/', '', $resource->file_path))) {
                Storage::disk('public')->delete(str_replace('/uploads/', '', $resource->file_path));
            }
            $resource->file_path = null;
            $resource->is_deleted_content = true;
            $resource->save();
            
            $deletedBytes += $resource->file_size_bytes;
            $this->info("Deleted file content for resource {$resource->id} size {$resource->file_size_bytes}");
        }

        $this->info("Cleanup done, deleted $deletedBytes bytes");
    }
}
