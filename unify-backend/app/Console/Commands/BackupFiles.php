<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupFiles extends Command
{
    protected $signature = 'backup:files {--compress}';
    protected $description = 'Backup uploaded files with optional compression';

    public function handle()
    {
        $filename = 'files_backup_' . now()->format('Y-m-d_H-i-s') . '.tar';
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $path = $backupDir . '/' . $filename;
        $uploadsPath = storage_path('app/public/uploads');

        if (!is_dir($uploadsPath)) {
            $this->warn('No uploads directory found. Skipping file backup.');
            return 0;
        }

        $command = sprintf(
            'tar -cf %s -C %s uploads 2>&1',
            escapeshellarg($path),
            escapeshellarg(storage_path('app/public'))
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
            $compressedPath = $path . '.gz';
            exec("gzip -f " . escapeshellarg($path));
            $path = $compressedPath;
            $filename .= '.gz';
        }

        Log::info('File backup created', ['file' => $filename]);
        $this->info("File backup created successfully: {$filename}");

        return 0;
    }
}