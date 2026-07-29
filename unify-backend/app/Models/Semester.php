<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id', 'name', 'is_current', 'global_state',
        'start_date_g', 'end_date_g', 'grace_period_ends_at', 'grace_period_handled'
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'grace_period_ends_at' => 'datetime',
        'grace_period_handled' => 'boolean',
    ];
}