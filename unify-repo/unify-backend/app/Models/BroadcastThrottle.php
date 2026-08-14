<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastThrottle extends Model
{
    protected $table = 'broadcast_throttles';

    protected $fillable = ['specification_id', 'professor_id', 'last_sent_at'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];
}
