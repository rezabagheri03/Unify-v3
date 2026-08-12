<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enrollment extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'specification_id', 'semester_id',
        'status', 'academic_status_at_enrollment', 'enrolled_at',
        'finalized_at', 'version'
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function specification()
    {
        return $this->belongsTo(CourseSpecification::class, 'specification_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}