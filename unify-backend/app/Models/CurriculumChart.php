<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumChart extends Model
{
    protected $table = 'curriculum_charts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'department_id', 'entry_year', 'chart_data', 'status',
        'approver_id', 'approved_at', 'version',
    ];

    protected $casts = [
        'chart_data' => 'json',
        'approved_at' => 'datetime',
        'version' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
