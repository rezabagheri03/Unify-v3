<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use SoftDeletes, HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'course_id', 'professor_id', 'specification_id', 'uploader_id',
        'title', 'description', 'file_path', 'temp_path', 'file_size_bytes', 'file_mime',
        'shamsi_original', 'created_at_g', 'status', 'version',
        'previous_version_id', 'family_id', 'scheduled_hard_delete_at',
        'average_rating', 'rating_count', 'download_count',
        'last_downloaded_at', 'badge_type', 'is_superseded',
        'is_deleted_content', 'is_protected'
    ];

    protected $casts = [
        'is_superseded' => 'boolean',
        'is_deleted_content' => 'boolean',
        'is_protected' => 'boolean',
        'created_at_g' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'scheduled_hard_delete_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function specification()
    {
        return $this->belongsTo(CourseSpecification::class, 'specification_id');
    }

    public function ratings()
    {
        return $this->hasMany(ResourceRating::class, 'resource_family_id', 'family_id');
    }

    public function stickyNotes()
    {
        return $this->hasMany(ResourceStickyNote::class, 'resource_family_id', 'family_id');
    }

    // FIX C1: Observer will set family_id = id after creation for first version
}