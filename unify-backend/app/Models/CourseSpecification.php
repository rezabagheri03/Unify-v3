<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseSpecification extends Model
{
    use HasFactory;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'course_id', 'professor_id', 'day_of_week', 'time_start', 'time_end',
        'is_next_day', 'location', 'telegram_link', 'exam_date_final_g',
        'shamsi_original_final', 'exam_date_midterm_g', 'shamsi_original_midterm',
        'is_active', 'semester_id'
    ];

    protected $casts = [
        'is_next_day' => 'boolean',
        'is_active' => 'boolean',
        'exam_date_final_g' => 'datetime',
        'exam_date_midterm_g' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}