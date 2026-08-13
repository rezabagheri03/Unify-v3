<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceDownloadLog extends Model
{
    protected $table = 'resource_download_logs';

    public $timestamps = false;

    protected $fillable = ['resource_id', 'student_id', 'downloaded_at', 'file_size_bytes'];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'file_size_bytes' => 'integer',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
