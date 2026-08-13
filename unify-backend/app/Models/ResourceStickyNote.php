<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceStickyNote extends Model
{
    protected $table = 'resource_sticky_notes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'student_id', 'resource_family_id', 'note'];
}