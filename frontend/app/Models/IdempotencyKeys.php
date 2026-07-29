<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKeys extends Model
{
    protected $table = 'idempotency_keys';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'key', 'user_id', 'response_code', 'response_body', 'expires_at'
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime',
    ];
}