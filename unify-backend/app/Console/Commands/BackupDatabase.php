<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--compress} {--keep-days=14}';
    protected $description = 'Create a database backup with optional compression';

    public function handle()
    {
        $filename = 'db_backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $backupDir = storage_path('app/backups');
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $path = $backupDir . '/' . $filename;

        $command = sprintf(
            'mysqldump --single-transaction --routines --triggers -u%s -p%s %s > %s 2>&1',
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.database')),
            escapeshellarg($path)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error('Database backup failed', ['output' => $output]);
            $this->error('Database backup failed!');
            return 1;
        }

        // Optional compression
        if ($this->option('compress')) {
            $compressedPath = $path . '.gz';
            exec("gzip -f " . escapeshellarg($path));
            $path = $compressedPath;
            $filename .= '.gz';
        }

        // Retention: drop db backups older than --keep-days (default 14).
        $cutoff = time() - ((int) $this->option('keep-days') * 86400);
        foreach (glob($backupDir . '/db_backup_*.sql*') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < $cutoff) {
                @unlink($old);
            }
        }

        Log::info('Database backup created', ['file' => $filename]);
        $this->info("Database backup created successfully: {$filename}");

        return 0;
    }
}