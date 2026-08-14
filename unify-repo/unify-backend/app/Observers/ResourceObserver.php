<?php

namespace App\Observers;

use App\Models\Resource;

class ResourceObserver
{
    public function created(Resource $resource)
    {
        // FIX C1: Set family_id = id for first version
        if (is_null($resource->family_id)) {
            $resource->updateQuietly(['family_id' => $resource->id]);
        }
    }
}