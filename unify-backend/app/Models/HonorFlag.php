<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HonorFlag extends Model
{
    protected $table = 'honor_flags';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'flag_type', 'count', 'last_declared_at',
        'resolved', 'resolve_reason',
    ];

    protected $casts = [
        'count' => 'integer',
        'last_declared_at' => 'datetime',
        'resolved' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
