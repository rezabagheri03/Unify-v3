<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    /**
     * Approve: move the staged file (temp_path) to its permanent /uploads path,
     * set badge, notify the uploader (polling + Pushe via Notification table).
     */
    public function approve(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        if ($resource->status !== 'pending') {
            return response()->json(['message' => 'فقط منابع در انتظار قابل تأییدند', 'code' => 'INVALID_STATE'], 400);
        }

        if ($resource->temp_path && Storage::disk('public')->exists($resource->temp_path)) {
            $finalPath = $resource->file_path ?? "resources/{$resource->course_id}/{$resource->professor_id}/" . basename($resource->temp_path);
            Storage::disk('public')->move($resource->temp_path, $finalPath);
            $resource->update([
                'status' => 'approved',
                'badge_type' => $resource->badge_type ?? 'expert_approved',
                'file_path' => $finalPath,
                'temp_path' => null,
            ]);
        } else {
            // No staged file (e.g. professor auto-approved path) — just approve.
            $resource->update([
                'status' => 'approved',
                'badge_type' => $resource->badge_type ?? 'expert_approved',
            ]);
        }

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $resource->uploader_id,
            'type' => 'resource_new',
            'title' => 'منبع شما تأیید شد',
            'body' => $resource->title,
            'priority' => 'high',
            'created_at' => now(),
        ]);

        // TODO(Pushe): DeviceToken lookup + PusheService->send() for android users.

        return response()->json(['message' => 'منبع تأیید شد', 'resource' => $resource->fresh()]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $resource = Resource::findOrFail($id);

        if ($resource->temp_path && Storage::disk('public')->exists($resource->temp_path)) {
            Storage::disk('public')->delete($resource->temp_path);
        }

        $resource->update([
            'status' => 'rejected',
            'temp_path' => null,
            'file_path' => null,
        ]);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $resource->uploader_id,
            'type' => 'resource_new',
            'title' => 'منبع شما رد شد',
            'body' => $resource->title . ($request->reason ? ' - ' . $request->reason : ''),
            'priority' => 'low',
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'منبع رد شد']);
    }
}
