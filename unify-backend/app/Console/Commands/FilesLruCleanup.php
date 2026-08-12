<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

class FilesLruCleanup extends Command
{
    protected $signature = 'files:lru-cleanup';
    protected $description = 'LRU cleanup for 50GB storage (delete non-protected least recently downloaded)';

    public function handle()
    {
        $totalSize = Resource::where('is_deleted_content', false)->sum('file_size_bytes');
        $limit = 40 * 1024 * 1024 * 1024; // 40GB (80% of 50GB)

        if ($totalSize <= $limit) {
            $this->info('Storage under limit.');
            return;
        }

        $toDelete = Resource::where('is_protected', false)
            ->where('is_deleted_content', false)
            ->orderBy('last_downloaded_at', 'asc')
            ->limit(50)
            ->get();

        foreach ($toDelete as $res) {
            if ($res->file_path && Storage::disk('public')->exists($res->file_path)) {
                Storage::disk('public')->delete($res->file_path);
            }
            $res->update([
                'is_deleted_content' => true,
                'file_path' => null
            ]);
        }

        $this->info(count($toDelete) . ' files cleaned via LRU.');
    }
}