<?php

namespace App\Console\Commands;

use App\Models\Resource;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Round-2 (audit V2-05): the staging area (`temp/` on the secured disk) had
 * no owner — files from crashed uploads and resources stuck in "pending"
 * accumulated forever while the 5/day quota only covered new uploads.
 *
 * Two bounded behaviors:
 *  1) ORPHANS: staged files on disk that no resource row references anymore
 *     and are older than 24h (a crashed upload is cleaned). Never younger —
 *     an in-flight upload would otherwise lose its file mid-request.
 *  2) STALE PENDINGS: resources nobody reviewed for 14 days are auto-rejected
 *     (file deleted, uploader notified) so "pending" cannot be used as
 *     unbounded free storage.
 */
class FilesCleanupStaging extends Command
{
    protected $signature = 'files:cleanup-staging';
    protected $description = 'Sweeps orphaned staged uploads and auto-rejects stale pending resources';

    private const DISK = 'local';
    private const ORPHAN_AGE_HOURS = 24;
    private const PENDING_TTL_DAYS = 14;

    public function handle(): void
    {
        $orphans = 0;
        $referenced = Resource::whereNotNull('temp_path')->pluck('temp_path')->flip();

        foreach (Storage::disk(self::DISK)->allFiles('temp') as $path) {
            if ($referenced->has($path)) {
                continue;
            }
            try {
                if (Storage::disk(self::DISK)->lastModified($path) > now()->subHours(self::ORPHAN_AGE_HOURS)->timestamp) {
                    continue; // too fresh — could belong to an in-flight upload
                }
            } catch (\Throwable $e) {
                continue;
            }
            Storage::disk(self::DISK)->delete($path);
            $orphans++;
        }

        $stale = Resource::where('status', 'pending')
            ->where('created_at_g', '<', now()->subDays(self::PENDING_TTL_DAYS))
            ->get();

        foreach ($stale as $res) {
            if ($res->temp_path) {
                foreach ([self::DISK, 'public'] as $disk) {
                    if (Storage::disk($disk)->exists($res->temp_path)) {
                        Storage::disk($disk)->delete($res->temp_path);
                        break;
                    }
                }
            }
            $res->update(['status' => 'rejected', 'temp_path' => null, 'file_path' => null]);

            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $res->uploader_id,
                'type' => 'resource_new',
                'title' => 'منبع شما به دلیل عدم بررسی رد شد',
                'body' => $res->title . ' — بیش از ' . self::PENDING_TTL_DAYS . ' روز در انتظار بود',
                'priority' => 'low',
                'created_at' => now(),
            ]);
            Cache::forget("notifications:unread:{$res->uploader_id}");
        }

        $this->info("staging cleanup: {$orphans} orphan files, {$stale->count()} stale pendings auto-rejected.");
    }
}
