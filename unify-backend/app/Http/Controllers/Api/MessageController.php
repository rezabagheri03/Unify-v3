<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->get('tab', 'all');

        $query = Message::where(function($q) use ($user) {
            $q->where('recipient_id', $user->id)
              ->orWhere(function($q2) use ($user) {
                  $q2->whereNull('recipient_id')
                     ->whereIn('specification_id', function($sub) use ($user) {
                         $sub->select('specification_id')
                             ->from('enrollments')
                             ->where('student_id', $user->id)
                             ->where('status', 'finalized');
                     });
              });
        })->where('is_deleted', false);

        if ($tab === 'unread') {
            $query->whereDoesntHave('readStatus', fn($q) => $q->where('user_id', $user->id));
        }

        $messages = $query->orderBy('sent_at', 'desc')->paginate(20);

        return response()->json(['data' => $messages->items()]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:2000',
            'specification_id' => 'nullable|exists:course_specifications,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();

        $message = Message::create([
            'id' => Str::uuid(),
            'sender_id' => $user->id,
            'recipient_id' => $request->recipient_id,
            'specification_id' => $request->specification_id,
            'subject' => $request->subject,
            'body' => $request->body,
            'sent_at' => now(),
            'priority' => 'normal',
        ]);

        return response()->json($message, 201);
    }
}