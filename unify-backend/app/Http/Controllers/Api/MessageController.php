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

        // SEC-04 fix: trim sender to non-PII columns (mobile/email never ship).
        $messages = $query->with(['sender:' . \App\Models\User::PUBLIC_COLS, 'specification.course'])
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
        $message = Message::with(['sender:' . \App\Models\User::PUBLIC_COLS, 'specification.course', 'replies.sender:' . \App\Models\User::PUBLIC_COLS])
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
            // SEC-02 fix: these were accepted unconditionally and stored raw.
            'priority' => 'nullable|in:low,normal,high',
            'parent_message_id' => 'nullable|exists:messages,id',
        ]);

        $body = InputSanitizer::clean($request->body, 2000);
        $subject = InputSanitizer::clean($request->subject, 255);

        $isBroadcast = $request->filled('specification_id') && ! $request->filled('recipient_id');

        // SEC-02 fix (F07): class broadcasts are a staff capability. Students
        // and heads-of-dept may only send direct messages.
        if ($isBroadcast && ! in_array($user->role, ['professor', 'expert', 'admin', 'owner'])) {
            return response()->json(['message' => 'پخش پیام به کلاس فقط برای اساتید و کارشناسان مجاز است', 'code' => 'FORBIDDEN'], 403);
        }

        // SEC-02 fix: a professor may only broadcast to a class they actually
        // teach; expert/admin/owner act in an official cross-class capacity.
        if ($isBroadcast && $user->role === 'professor') {
            $ownsSpec = \App\Models\CourseSpecification::where('id', $request->specification_id)
                ->where('professor_id', $user->id)
                ->exists();
            if (! $ownsSpec) {
                return response()->json(['message' => 'فقط برای کلاس‌های خودتان می‌توانید پیام همگانی بفرستید', 'code' => 'FORBIDDEN'], 403);
            }
        }

        // Broadcast throttle: 1 per 10 min per professor per spec (F07).
        // Post-audit F-13: the gate is a single conditional UPDATE on the
        // unique (specification, professor) row — the old firstOrCreate +
        // check-then-act let two parallel sends both pass.
        $throttleRowIds = null;
        if ($isBroadcast) {
            $spec = $request->specification_id;
            $claimed = (bool) BroadcastThrottle::where('specification_id', $spec)
                ->where('professor_id', $user->id)
                ->where('last_sent_at', '<=', now()->subMinutes(10))
                ->update(['last_sent_at' => now()]);

            if (! $claimed) {
                $exists = BroadcastThrottle::where('specification_id', $spec)
                    ->where('professor_id', $user->id)->exists();
                if (! $exists) {
                    try {
                        BroadcastThrottle::create([
                            'specification_id' => $spec,
                            'professor_id' => $user->id,
                            'last_sent_at' => now(),
                        ]);
                        $claimed = true;
                    } catch (\Illuminate\Database\QueryException $e) {
                        $claimed = false; // lost the insert race — treat as throttled
                    }
                }
            }

            if (! $claimed) {
                $last = BroadcastThrottle::where('specification_id', $spec)
                    ->where('professor_id', $user->id)->value('last_sent_at');
                // Carbon 3: diffs are SIGNED — (past)->diffInSeconds(now) is the
                // positive elapsed-seconds direction (the codebase's convention).
                $retrySec = $last ? max(1, 600 - (int) \Carbon\Carbon::parse($last)->diffInSeconds(now())) : 600; // V2-13: exact remainder
                return response()->json([
                    'message' => 'پخش پیام محدود شده - چند دقیقه بعد تلاش کنید',
                    'code' => 'BROADCAST_THROTTLED',
                    'retry_after' => $retrySec,
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
            // Throttle row was stamped by the atomic gate above.

            // D-006: push to the class on broadcast, gated by services.pushe.enabled.
            if (config('services.pushe.enabled')) {
                $targetIds = Enrollment::where('specification_id', $request->specification_id)
                    ->where('status', 'finalized')
                    ->pluck('student_id')
                    ->all();
                (new \App\Services\PusheService())->send($targetIds, $subject, Str::limit($body, 120));
            }
        }

        return response()->json($message->load('sender:' . \App\Models\User::PUBLIC_COLS), 201);
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

        // Post-audit F-13: the tombstone leaked the SUBJECT — scrub both fields.
        $message->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'subject' => 'این پیام توسط فرستنده حذف شد',
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
