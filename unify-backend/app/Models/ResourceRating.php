<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceRating extends Model
{
    protected $table = 'resource_ratings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'student_id', 'resource_family_id', 'rating', 'rated_at', 'is_self_rating'];
}