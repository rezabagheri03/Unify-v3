<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestoreFiles extends Command
{
    protected $signature = 'restore:files {file}';
    protected $description = 'Restore uploaded files from a backup';

    public function handle()
    {
        $file = $this->argument('file');
        $path = storage_path('app/backups/' . $file);

        if (!file_exists($path)) {
            $this->error("Backup file not found: {$path}");
            return 1;
        }

        $command = sprintf(
            'tar -xzf %s -C %s',
            $path,
            storage_path('app/public')
        );

        exec($command);

        $this->info("Files restored from: {$file}");
    }
}