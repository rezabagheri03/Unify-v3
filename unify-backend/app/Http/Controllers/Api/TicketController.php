<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketDailyCount;
use App\Models\Notification;
use App\Services\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['student', 'assignedExpert']);

        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'expert' || $user->role === 'head_of_dept') {
            $query->where('department', $user->department_id);
        }
        // admin/owner: all

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->boolean('assigned_to_me')) {
            $query->where('assigned_to', $user->id);
        }
        if ($request->boolean('escalated')) {
            $query->where('is_escalated', true);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('subject', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%"));
        }

        return response()->json($query->orderByDesc('created_at')->paginate(15));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json(['message' => 'فقط دانشجو می‌تواند تیکت ثبت کند', 'code' => 'FORBIDDEN'], 403);
        }

        // 5/day limit (F08)
        $today = now()->toDateString();
        $count = TicketDailyCount::where('student_id', $user->id)->where('date', $today)->value('count') ?? 0;
        if ($count >= 5) {
            return response()->json(['message' => 'سقف روزانه ۵ تیکت', 'code' => 'QUOTA_EXCEEDED'], 429);
        }

        $request->validate([
            'department' => 'required|in:education,technical,student_affairs',
            'subject' => 'required|string|max:100',
            'description' => 'required|string|max:2000',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            'related_ticket_id' => 'nullable|exists:tickets,id',
        ]);

        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('tickets/' . $user->id, 'public'),
                'size' => $file->getSize(),
            ];
        }

        $subject = InputSanitizer::clean($request->subject, 100);
        if ($request->filled('related_ticket_id')) {
            $subject = '[مرتبط با #' . substr($request->related_ticket_id, 0, 8) . '] ' . $subject;
        }

        $ticket = Ticket::create([
            'id' => Str::uuid(),
            'student_id' => $user->id,
            'department' => $request->department,
            'subject' => $subject,
            'description' => InputSanitizer::clean($request->description, 2000),
            'status' => 'open',
            'student_attachments' => $attachments,
        ]);

        $daily = TicketDailyCount::firstOrCreate(
            ['student_id' => $user->id, 'date' => $today],
            ['count' => 0]
        );
        $daily->increment('count');

        return response()->json($ticket->load('student'), 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $ticket = Ticket::with(['student', 'assignedExpert', 'replies.sender'])->findOrFail($id);

        if (! $this->canView($user, $ticket)) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        return response()->json($ticket);
    }

    public function reply(Request $request, $ticketId)
    {
        $user = $request->user();
        $ticket = Ticket::findOrFail($ticketId);

        if (! $this->canView($user, $ticket)) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'message' => 'تیکت بسته شده - تیکت جدید ثبت کنید',
                'code' => 'TICKET_CLOSED',
                'suggested_action' => 'create_related',
                'related_id' => $ticket->id,
            ], 403);
        }

        $request->validate([
            'body' => 'required|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,docx,zip,jpg,jpeg,png|max:20480',
        ]);

        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('tickets/' . $ticket->id . '/replies', 'public'),
                'size' => $file->getSize(),
            ];
        }

        $isStaff = in_array($user->role, ['expert', 'admin', 'owner', 'head_of_dept']);

        TicketReply::create([
            'id' => Str::uuid(),
            'ticket_id' => $ticket->id,
            'sender_id' => $user->id,
            'body' => InputSanitizer::clean($request->body, 2000),
            'attachments' => $attachments,
            'sent_at' => now(),
            'is_staff' => $isStaff,
        ]);

        // State machine (F08)
        if ($isStaff) {
            $ticket->update(['status' => 'answered']);
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $ticket->student_id,
                'type' => 'ticket_answered',
                'title' => 'پاسخ جدید برای تیکت شما',
                'body' => $ticket->subject,
                'priority' => 'high',
                'created_at' => now(),
            ]);
        } elseif ($ticket->status === 'answered') {
            $ticket->update(['status' => 'open']);
        }

        return response()->json($ticket->fresh(['replies.sender']), 201);
    }

    public function updateStatus(Request $request, $ticketId)
    {
        $user = $request->user();
        $ticket = Ticket::findOrFail($ticketId);

        if (! in_array($user->role, ['expert', 'admin', 'owner'])) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        $request->validate([
            'status' => 'required|in:in_progress,closed',
            'close_reason' => 'nullable|string|max:500',
        ]);

        if ($request->status === 'closed') {
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $ticket->student_id,
                'type' => 'ticket_answered',
                'title' => 'تیکت شما بسته شد',
                'body' => $ticket->subject . ($request->close_reason ? ' - ' . $request->close_reason : ''),
                'priority' => 'low',
                'created_at' => now(),
            ]);
        } else {
            $ticket->update(['status' => 'in_progress', 'assigned_to' => $user->id]);
        }

        return response()->json($ticket);
    }

    public function assign(Request $request, $ticketId)
    {
        $user = $request->user();
        $ticket = Ticket::findOrFail($ticketId);

        if (! in_array($user->role, ['expert', 'admin', 'owner'])) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        $ticket->update(['assigned_to' => $user->id, 'status' => 'in_progress']);
        return response()->json($ticket);
    }

    private function canView($user, Ticket $ticket): bool
    {
        if ($user->role === 'student') {
            return $ticket->student_id === $user->id;
        }
        // F08 queue filters: "Dept own dept, Expert all, Admin all"
        // -> expert/admin/owner/head handle all service-department tickets.
        return in_array($user->role, ['expert', 'head_of_dept', 'admin', 'owner']);
    }
}
