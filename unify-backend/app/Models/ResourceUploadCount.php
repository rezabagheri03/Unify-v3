<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceUploadCount extends Model
{
    protected $table = 'resource_upload_counts';

    public $timestamps = false;

    protected $fillable = ['user_id', 'date', 'count'];
}
