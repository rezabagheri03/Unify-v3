<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// FIX H1: Cleanup expired idempotency keys older than 24h - table grows indefinitely 12k rows/day
class IdempotencyCleanup extends Command
{
    protected $signature = 'idempotency:cleanup';
    protected $description = 'Delete expired idempotency keys older than 24h';

    public function handle()
    {
        $deleted = DB::table('idempotency_keys')->where('expires_at', '<', now())->delete();
        $this->info("Deleted $deleted expired idempotency keys");
        // Log to AuditLog? Optional
    }
}
