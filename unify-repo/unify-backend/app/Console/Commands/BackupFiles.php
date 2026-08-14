<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupFiles extends Command
{
    protected $signature = 'backup:files {--compress} {--keep-days=14}';
    protected $description = 'Backup uploaded files with optional compression';

    /**
     * Real payload directories under storage/app (DB audit fix: the old command
     * tarred `storage/app/public/uploads`, an empty placeholder, while actual
     * files live in `resources`, `temp`, `public/resources`, `public/uploads`
     * (legacy) and `public/branding`).
     */
    private const CANDIDATE_DIRS = [
        'resources',
        'temp',
        'public/resources',
        'public/uploads',
        'public/branding',
    ];

    public function handle()
    {
        $filename = 'files_backup_' . now()->format('Y-m-d_H-i-s') . '.tar';
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $path = $backupDir . '/' . $filename;

        $targets = [];
        foreach (self::CANDIDATE_DIRS as $dir) {
            if (is_dir(storage_path('app/' . $dir))) {
                $targets[] = $dir;
            }
        }

        if (empty($targets)) {
            $this->warn('No upload directories found. Skipping file backup.');
            return 0;
        }

        $command = sprintf(
            'tar -cf %s -C %s %s 2>&1',
            escapeshellarg($path),
            escapeshellarg(storage_path('app')),
            implode(' ', array_map('escapeshellarg', $targets))
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error('File backup failed', ['output' => $output]);
            $this->error('File backup failed!');
            return 1;
        }

        // Optional compression
        if ($this->option('compress')) {
            exec('gzip -f ' . escapeshellarg($path));
            $path .= '.gz';
            $filename .= '.gz';
        }

        $this->pruneOld($backupDir, (int) $this->option('keep-days'));

        Log::info('File backup created', ['file' => $filename, 'dirs' => $targets]);
        $this->info("File backup created successfully: {$filename}");

        return 0;
    }

    /** Simple retention: delete file backups older than N days (default 14). */
    private function pruneOld(string $backupDir, int $keepDays): void
    {
        $cutoff = time() - ($keepDays * 86400);
        foreach (glob($backupDir . '/files_backup_*.tar*') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < $cutoff) {
                @unlink($old);
            }
        }
    }
}
