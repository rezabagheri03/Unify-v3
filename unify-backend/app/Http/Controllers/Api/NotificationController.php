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

        $mutedSpecIds = NotificationMute::where('user_id', $user->id)
            ->where('muted', true)
            ->pluck('specification_id');

        // Round-2 (V2-17): mutes used to be write-only — nothing ever read
        // them, so "muting a class" changed nothing. Notifications keyed to a
        // muted spec (via data->specification_id) no longer reach the feed.
        return Cache::remember($cacheKey, 5, function () use ($user, $since, $mutedSpecIds) {
            $items = Notification::where('user_id', $user->id)
                ->where('read', false)
                ->where('created_at', '>', $since)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            if ($mutedSpecIds->isNotEmpty()) {
                $items = $items->reject(fn ($n) =>
                    is_array($n->data)
                    && isset($n->data['specification_id'])
                    && $mutedSpecIds->contains($n->data['specification_id'])
                )->values();
            }

            return $items;
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

        // Round-2 fix: notification_mutes has a COMPOSITE key and no id
        // column — Eloquent's updateOrCreate->save() builds its UPDATE keyed
        // on the (absent) primary key, silently updating 0 rows. Unmute could
        // never work; the write-only consumer hid it. Go through the query
        // builder keyed on the real columns.
        $existing = NotificationMute::where('user_id', $request->user()->id)
            ->where('specification_id', $request->specification_id)
            ->first();
        if ($existing) {
            NotificationMute::where('user_id', $request->user()->id)
                ->where('specification_id', $request->specification_id)
                ->update(['muted' => $request->boolean('muted')]);
        } else {
            NotificationMute::create([
                'user_id' => $request->user()->id,
                'specification_id' => $request->specification_id,
                'muted' => $request->boolean('muted'),
            ]);
        }
        Cache::forget("notifications:unread:{$request->user()->id}");
        Cache::forget("notifications:unread:{$request->user()->id}");

        return response()->json(['success' => true]);
    }
}
