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
        // Round-2 (V2-06): limit is config-overridable so the post-fix behavior
        // is testable without fabricating 40GB of rows.
        $limit = (int) config('unify.lru_limit_bytes', 40 * 1024 * 1024 * 1024); // 40GB (80% of 50GB)

        if ($totalSize <= $limit) {
            $this->info('Storage under limit.');
            return;
        }

        $toDelete = Resource::where('is_protected', false)
            ->where('is_deleted_content', false)
            ->orderBy('last_downloaded_at', 'asc')
            ->limit(50)
            ->get();

        $cleaned = 0;
        $skipped = 0;
        foreach ($toDelete as $res) {
            // Round-2 (V2-06): post-SEC-05 bytes live on the LOCAL disk — the
            // old public-only probe found nothing yet tombstoned the row
            // anyway, freeing 0 bytes while stats pretended otherwise. Resolve
            // the disk exactly like ResourceController::download does, and
            // ONLY tombstone when bytes were actually deleted.
            $disk = null;
            if ($res->file_path) {
                if (Storage::disk('local')->exists($res->file_path)) {
                    $disk = 'local';
                } elseif (Storage::disk('public')->exists($res->file_path)) {
                    $disk = 'public';
                }
            }
            if (! $disk) {
                $skipped++;
                continue; // nothing on disk — keep the row (and its fallback path) intact, count it
            }

            Storage::disk($disk)->delete($res->file_path);
            $res->update([
                'is_deleted_content' => true,
                'file_path' => null,
            ]);
            $cleaned++;
        }

        $this->info($cleaned . ' files cleaned via LRU' . ($skipped ? " ({$skipped} rows skipped — file not on any disk)" : '') . '.');
    }
}