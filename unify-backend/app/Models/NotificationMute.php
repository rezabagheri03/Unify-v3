<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-specification notification mutes.
 * Table uses a composite primary key (user_id, specification_id) and has no timestamps.
 */
class NotificationMute extends Model
{
    protected $table = 'notification_mutes';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_id', 'specification_id', 'muted'];

    protected $casts = [
        'muted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function specification()
    {
        return $this->belongsTo(CourseSpecification::class, 'specification_id');
    }
}
