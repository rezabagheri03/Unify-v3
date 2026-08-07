<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReadStatus;
use App\Models\BroadcastThrottle;
use App\Models\Enrollment;
use App\Services\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Unified inbox with tabs (F07):
     * all | unread | classes | private | system.
     * Broadcast fan-out: recipient_id null + specification_id set; students see
     * messages for specs they are enrolled in (finalized).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->get('tab', 'all');

        $enrolledSpecIds = Enrollment::where('student_id', $user->id)
            ->where('status', 'finalized')
            ->pluck('specification_id');

        $query = Message::query()
            ->where(function ($q) use ($user, $enrolledSpecIds) {
                $q->where('recipient_id', $user->id)
                  ->orWhere(function ($q2) use ($enrolledSpecIds) {
                      $q2->whereNull('recipient_id')
                         ->whereIn('specification_id', $enrolledSpecIds);
                  });
            });

        switch ($tab) {
            case 'unread':
                $query->whereDoesntHave('readStatus', fn ($q) => $q->where('user_id', $user->id));
                break;
            case 'classes':
                $query->whereNull('recipient_id')->whereNotNull('specification_id');
                break;
            case 'private':
                $query->whereNotNull('recipient_id');
                break;
            case 'system':
                $query->where(function ($q) {
                    $q->whereNull('recipient_id')->whereNull('specification_id');
                });
                break;
        }

        $messages = $query->with(['sender', 'specification.course'])
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'total_pages' => $messages->lastPage(),
                'total_items' => $messages->total(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $message = Message::with(['sender', 'specification.course', 'replies.sender'])
            ->findOrFail($id);

        // Authorization: recipient, sender, or enrolled in broadcast spec
        $enrolled = Enrollment::where('student_id', $user->id)
            ->where('specification_id', $message->specification_id)
            ->where('status', 'finalized')
            ->exists();

        if ($message->recipient_id !== $user->id && $message->sender_id !== $user->id && ! $enrolled) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        return response()->json($message);
    }

    public function send(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
            'specification_id' => 'nullable|exists:course_specifications,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        $body = InputSanitizer::clean($request->body, 2000);
        $subject = InputSanitizer::clean($request->subject, 255);

        $isBroadcast = $request->filled('specification_id') && ! $request->filled('recipient_id');

        // Broadcast throttle: 1 per 10 min per professor per spec (F07)
        if ($isBroadcast) {
            $throttle = BroadcastThrottle::firstOrCreate(
                [
                    'specification_id' => $request->specification_id,
                    'professor_id' => $user->id,
                ],
                ['last_sent_at' => now()->subMinutes(11)]
            );
            if ($throttle->last_sent_at->diffInMinutes(now()) < 10) {
                $retry = 10 - (int) $throttle->last_sent_at->diffInMinutes(now());
                return response()->json([
                    'message' => "پخش پیام محدود شده - {$retry} دقیقه بعد تلاش کنید",
                    'code' => 'BROADCAST_THROTTLED',
                    'retry_after' => max(1, $retry) * 60,
                ], 429);
            }
        }

        // Banned recipient: only admin/owner may message (F07)
        if ($request->filled('recipient_id')) {
            $recipient = \App\Models\User::find($request->recipient_id);
            if ($recipient && $recipient->is_banned && ! in_array($user->role, ['admin', 'owner'])) {
                return response()->json([
                    'message' => 'کاربر بن شده - فقط ادمین می‌تواند پیام دهد',
                    'code' => 'CANNOT_MESSAGE_BANNED',
                ], 403);
            }
        }

        $message = Message::create([
            'id' => Str::uuid(),
            'sender_id' => $user->id,
            'recipient_id' => $request->recipient_id,
            'specification_id' => $isBroadcast ? $request->specification_id : null,
            'subject' => $subject,
            'body' => $body,
            'sent_at' => now(),
            'priority' => $request->get('priority', 'normal'),
            'parent_message_id' => $request->parent_message_id,
        ]);

        if ($isBroadcast) {
            BroadcastThrottle::where('specification_id', $request->specification_id)
                ->where('professor_id', $user->id)
                ->update(['last_sent_at' => now()]);
        }

        return response()->json($message->load('sender'), 201);
    }

    /** Edit: only sender, sets is_edited (F07). */
    public function edit(Request $request, $id)
    {
        $user = $request->user();
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'فقط فرستنده می‌تواند ویرایش کند', 'code' => 'FORBIDDEN'], 403);
        }
        if ($message->is_deleted) {
            return response()->json(['message' => 'پیام حذف شده قابل ویرایش نیست', 'code' => 'MESSAGE_DELETED'], 400);
        }

        $request->validate(['body' => 'required|string|max:2000']);
        $message->update([
            'body' => InputSanitizer::clean($request->body, 2000),
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return response()->json($message);
    }

    /** Soft delete: placeholder kept for consistency (F07). */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'فقط فرستنده می‌تواند حذف کند', 'code' => 'FORBIDDEN'], 403);
        }

        $message->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'body' => 'این پیام توسط فرستنده حذف شد',
        ]);

        return response()->json(['message' => 'پیام حذف شد']);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $message = Message::findOrFail($id);

        MessageReadStatus::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['id' => (string) Str::uuid(), 'read_at' => now()]
        );

        return response()->json(['success' => true]);
    }
}
