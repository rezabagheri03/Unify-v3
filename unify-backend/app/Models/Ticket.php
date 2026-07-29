<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'student_id', 'department', 'subject', 'description',
        'status', 'assigned_to', 'student_attachments', 'staff_attachments',
        'closed_at', 'escalated_at', 'is_escalated', 'escalation_level'
    ];

    protected $casts = [
        'student_attachments' => 'array',
        'staff_attachments' => 'array',
        'is_escalated' => 'boolean',
    ];
}