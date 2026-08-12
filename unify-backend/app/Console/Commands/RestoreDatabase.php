<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestoreDatabase extends Command
{
    protected $signature = 'restore:database {file}';
    protected $description = 'Restore database from a backup file';

    public function handle()
    {
        $file = $this->argument('file');
        $path = storage_path('app/backups/' . $file);

        if (!file_exists($path)) {
            $this->error("Backup file not found: {$path}");
            return 1;
        }

        $command = sprintf(
            'mysql -u%s -p%s %s < %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $path
        );

        exec($command);

        $this->info("Database restored from: {$file}");
    }
}