<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDailyCount extends Model
{
    protected $table = 'ticket_daily_counts';

    public $timestamps = false;

    protected $fillable = ['student_id', 'date', 'count'];
}
