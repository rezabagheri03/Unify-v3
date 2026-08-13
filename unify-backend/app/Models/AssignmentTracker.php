<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentTracker extends Model
{
    protected $table = 'assignment_trackers';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'specification_id', 'title', 'description',
        'due_date_g', 'shamsi_original', 'reminder_before_hours', 'status',
        'attachment_path', 'grade', 'graded_by', 'graded_at', 'submitted_at',
        'local_notification_scheduled',
    ];

    protected $casts = [
        'due_date_g' => 'datetime',
        'reminder_before_hours' => 'integer',
        'grade' => 'float',
        'graded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'local_notification_scheduled' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function specification(): BelongsTo
    {
        return $this->belongsTo(CourseSpecification::class, 'specification_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
