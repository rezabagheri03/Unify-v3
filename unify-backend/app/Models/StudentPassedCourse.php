<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPassedCourse extends Model
{
    protected $table = 'student_passed_courses';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'course_id', 'passed', 'grade', 'entry_year'
    ];
}