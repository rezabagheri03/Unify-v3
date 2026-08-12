<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResourceApprovalController extends Controller
{
    /** Secured upload disk (matches ResourceController::DISK). */
    private const DISK = 'local';

    public function pending(Request $request)
    {
        $resources = Resource::where('status', 'pending')
            // SEC-04 fix: trim user columns to non-PII fields.
            ->with(['course', 'uploader:id,first_name,last_name,role', 'professor:id,first_name,last_name'])
            ->orderBy('created_at_g', 'desc')
            ->paginate(20);

        return response()->json($resources);
    }

    /**
     * Approve: move the staged file (temp_path) to its permanent path on the
     * secured disk, supersede the previous version (F05/F06), set badge,
     * notify the uploader.
     */
    public function approve(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);
        if ($resource->status !== 'pending') {
            return response()->json(['message' => 'فقط منابع در انتظار قابل تأییدند', 'code' => 'INVALID_STATE'], 400);
        }

        if ($resource->temp_path) {
            $finalPath = $resource->file_path ?? "resources/{$resource->course_id}/{$resource->professor_id}/" . basename($resource->temp_path);

            if (Storage::disk(self::DISK)->exists($resource->temp_path)) {
                Storage::disk(self::DISK)->move($resource->temp_path, $finalPath);
            } elseif (Storage::disk('public')->exists($resource->temp_path)) {
                // Legacy staged file on the public disk: re-home it onto the
                // secured disk on approval.
                Storage::disk(self::DISK)->put($finalPath, Storage::disk('public')->get($resource->temp_path));
                Storage::disk('public')->delete($resource->temp_path);
            }

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

        // SEC-02 fix (version lifecycle): only NOW that the new version is
        // approved is the parent superseded (30-day hard-delete clock, F05).
        if ($resource->previous_version_id) {
            Resource::where('id', $resource->previous_version_id)
                ->where('is_superseded', false)
                ->update([
                    'is_superseded' => true,
                    'scheduled_hard_delete_at' => now()->addDays(30),
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
        Cache::forget("notifications:unread:{$resource->uploader_id}");

        // D-006: push on approval, gated by services.pushe.enabled (default off).
        if (config('services.pushe.enabled')) {
            (new \App\Services\PusheService())->send([(string) $resource->uploader_id], 'منبع شما تأیید شد', $resource->title);
        }

        return response()->json(['message' => 'منبع تأیید شد', 'resource' => $resource->fresh()]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $resource = Resource::findOrFail($id);

        // SEC-02 fix: rejection is only valid from the pending state.
        if ($resource->status !== 'pending') {
            return response()->json(['message' => 'فقط منابع در انتظار قابل رد هستند', 'code' => 'INVALID_STATE'], 400);
        }

        foreach ([self::DISK, 'public'] as $disk) {
            if ($resource->temp_path && Storage::disk($disk)->exists($resource->temp_path)) {
                Storage::disk($disk)->delete($resource->temp_path);
                break;
            }
        }

        $resource->update([
            'status' => 'rejected',
            'temp_path' => null,
            'file_path' => null,
        ]);

        // SEC-02 fix: never leave the parent superseded when its child version
        // was rejected (defensive restore — the normal flow never supersedes
        // before approval anymore).
        if ($resource->previous_version_id) {
            Resource::where('id', $resource->previous_version_id)
                ->update(['is_superseded' => false, 'scheduled_hard_delete_at' => null]);
        }

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $resource->uploader_id,
            'type' => 'resource_new',
            'title' => 'منبع شما رد شد',
            'body' => $resource->title . ($request->reason ? ' - ' . $request->reason : ''),
            'priority' => 'low',
            'created_at' => now(),
        ]);
        Cache::forget("notifications:unread:{$resource->uploader_id}");

        return response()->json(['message' => 'منبع رد شد']);
    }
}
