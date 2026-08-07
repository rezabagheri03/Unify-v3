<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicCalendar extends Model
{
    protected $table = 'academic_calendars';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'description', 'start_date_g', 'end_date_g',
        'shamsi_original_start', 'shamsi_original_end', 'event_type',
        'is_university_wide', 'department_id', 'color_code', 'created_by',
    ];

    protected $casts = [
        'start_date_g' => 'datetime',
        'end_date_g' => 'datetime',
        'is_university_wide' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
