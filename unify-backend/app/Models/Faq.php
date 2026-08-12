<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    

    protected $table = 'faqs';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'specification_id', 'question', 'answer', 'is_pinned', 'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
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
