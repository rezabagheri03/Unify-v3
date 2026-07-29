<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Ticket::query();

        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'expert') {
            $query->where('department', $user->department_id); // simplified
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department' => 'required|in:education,technical,student_affairs',
            'subject' => 'required|string|max:100',
            'description' => 'required|string|max:2000',
        ]);

        $ticket = Ticket::create([
            'id' => Str::uuid(),
            'student_id' => $request->user()->id,
            'department' => $request->department,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'open',
        ]);

        return response()->json($ticket, 201);
    }

    public function reply(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $user = $request->user();

        $reply = TicketReply::create([
            'id' => Str::uuid(),
            'ticket_id' => $ticket->id,
            'sender_id' => $user->id,
            'body' => $request->body,
            'sent_at' => now(),
            'is_staff' => !in_array($user->role, ['student']),
        ]);

        // Revert to open if student replies to answered ticket
        if ($ticket->status === 'answered' && $user->role === 'student') {
            $ticket->update(['status' => 'open']);
        }

        return response()->json($reply, 201);
    }
}