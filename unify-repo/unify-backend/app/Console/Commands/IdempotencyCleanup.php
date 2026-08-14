<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IdempotencyKeys;

class IdempotencyCleanup extends Command
{
    protected $signature = 'idempotency:cleanup';
    protected $description = 'Remove expired idempotency keys (H1 fix)';

    public function handle()
    {
        $deleted = IdempotencyKeys::where('expires_at', '<', now())->delete();
        $this->info("$deleted idempotency keys cleaned.");
    }
}