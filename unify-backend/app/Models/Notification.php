<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'user_id', 'type', 'title', 'body', 'data', 'priority', 'read', 'created_at'
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];
}