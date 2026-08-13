<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'timestamp',
        'ip_address',
        'user_agent',
        'details',
        'is_suspicious',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'is_suspicious' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->timestamp)) {
                $model->timestamp = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Post-audit F-04: single choke point for privileged-action audit rows.
     * Details are small non-sensitive JSON only — never passwords or tokens.
     * (The id/timestamp defaults come from the creating() hook above.)
     */
    public static function record(?string $userId, string $action, ?string $resourceType = null, string|int|null $resourceId = null, ?\Illuminate\Http\Request $request = null, array $details = []): self
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            // Columns are NOT NULL — imports/envelopes legitimately have none.
            'resource_type' => $resourceType ?? 'app',
            'resource_id' => (string) ($resourceId ?? '-'),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
