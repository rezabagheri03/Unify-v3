<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadDailyCount extends Model
{
    protected $table = 'download_daily_counts';
    public $timestamps = false;
    protected $fillable = ['student_id', 'date', 'count', 'total_bytes'];
}