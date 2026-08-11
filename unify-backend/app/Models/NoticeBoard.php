<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeBoard extends Model
{
    

    protected $table = 'notice_boards';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'specification_id', 'title', 'content', 'priority',
        'banner_color', 'expires_at', 'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function specification(): BelongsTo
    {
        return $this->belongsTo(CourseSpecification::class, 'specification_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
