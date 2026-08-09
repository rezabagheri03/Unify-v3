<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Notification;
use App\Services\PusheService;
use Illuminate\Http\Request;

class ResourceApprovalController extends Controller
{
    public function pending(Request $request)
    {
        $resources = Resource::where('status', 'pending')
            ->with(['course', 'uploader', 'professor'])
            ->orderBy('created_at_g', 'desc')
            ->paginate(20);

        return response()->json($resources);
    }

    public function approve(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        
        $resource->update([
            'status' => 'approved',
            'badge_type' => 'expert_approved',
        ]);

        // Notify uploader and enrolled students
        Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $resource->uploader_id,
            'type' => 'resource_approved',
            'title' => 'منبع شما تأیید شد',
            'body' => $resource->title,
            'priority' => 'high',
            'created_at' => now(),
        ]);

        // TODO: Send Pushe to enrolled students of the course

        return response()->json(['message' => 'منبع تأیید شد']);
    }

    public function reject(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        $resource->update(['status' => 'rejected']);

        return response()->json(['message' => 'منبع رد شد']);
    }
}