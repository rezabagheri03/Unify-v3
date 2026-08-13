<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdempotencyKeys extends Model
{
    use HasFactory;
    protected $table = 'idempotency_keys';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // table only has created_at (no updated_at)

    protected $fillable = [
        'id', 'key', 'user_id', 'response_code', 'response_body', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}
