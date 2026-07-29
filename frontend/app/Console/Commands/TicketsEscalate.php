<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\Notification;

class TicketsEscalate extends Command
{
    protected $signature = 'tickets:escalate';
    protected $description = 'Escalate tickets after 48 hours with no reply';

    public function handle()
    {
        $tickets = Ticket::where('status', '!=', 'closed')
            ->where('updated_at', '<=', now()->subHours(48))
            ->where('is_escalated', false)
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->update([
                'is_escalated' => true,
                'escalated_at' => now(),
                'escalation_level' => $ticket->escalation_level + 1,
            ]);

            Notification::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $ticket->assigned_to ?? '990000001', // Owner fallback
                'type' => 'ticket_escalated',
                'title' => 'تیکت escalate شد',
                'body' => $ticket->subject,
                'priority' => 'high',
                'created_at' => now(),
            ]);
        }

        $this->info(count($tickets) . ' tickets escalated.');
    }
}