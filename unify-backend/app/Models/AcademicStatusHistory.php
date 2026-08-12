<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicStatusHistory extends Model
{
    protected $table = 'academic_status_history';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'status', 'semester_id', 'declared_at', 'ip_address', 'user_agent'
    ];
}