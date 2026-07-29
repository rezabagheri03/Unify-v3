<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageStat extends Model
{
    protected $table = 'storage_stats';
    protected $fillable = ['total_bytes_used', 'total_bytes_limit', 'last_calculated_at'];
}