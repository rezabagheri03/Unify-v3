<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\AssignmentController;

class AssignmentsMarkLate extends Command
{
    protected $signature = 'assignments:mark-late';
    protected $description = 'Mark pending assignments past their due date as late (F12, hourly cron)';

    public function handle()
    {
        $count = AssignmentController::markLate();
        $this->info("$count assignments marked late.");
    }
}
