<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationMute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function unread(Request $request)
    {
        $user = $request->user();
        $since = $request->get('since', now()->subMinutes(5));

        // PERF-01 fix: the old key embedded the client-supplied `since`, making
        // hits ~impossible (30s poll >> 5s TTL) while every poll wrote a dead
        // cache row. The key is now per-user only; a hit may include items from
        // a slightly older `since` window, which the client de-duplicates by id.
        $cacheKey = "notifications:unread:{$user->id}";

        return Cache::remember($cacheKey, 5, function () use ($user, $since) {
            return Notification::where('user_id', $user->id)
                ->where('read', false)
                ->where('created_at', '>', $since)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        });
    }

    public function markRead(Request $request, $id)
    {
        Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['read' => true]);

        Cache::forget("notifications:unread:{$request->user()->id}");

        return response()->json(['success' => true]);
    }

    public function mute(Request $request)
    {
        $request->validate([
            'specification_id' => 'required|string',
            'muted' => 'required|boolean',
        ]);

        NotificationMute::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'specification_id' => $request->specification_id,
            ],
            ['muted' => $request->muted]
        );

        return response()->json(['success' => true]);
    }
}
