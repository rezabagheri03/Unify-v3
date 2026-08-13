<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldenScheduleCache extends Model
{
    protected $table = 'golden_schedule_caches';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'semester_id', 'preferences_hash', 'combos', 'generated_at', 'expires_at',
    ];

    protected $casts = [
        'combos' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
