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
        // TODO-034 fix: the old filter (`is_escalated = false`) made level 2
        // structurally unreachable. Levels are now explicit:
        //   L1: 48h without staff reply          -> assigned staff / admin
        //   L2: +48h more after L1, still open   -> owner + admin
        $escalated = 0;

        $level1 = Ticket::where('status', '!=', 'closed')
            ->where('is_escalated', false)
            ->where('updated_at', '<=', now()->subHours((int) config('unify.ticket_escalation_hours', 48)))
            ->get();

        foreach ($level1 as $ticket) {
            $ticket->update([
                'is_escalated' => true,
                'escalated_at' => now(),
                'escalation_level' => 1,
            ]);
            $this->notify($ticket, ['admin'], $ticket->assigned_to);
            $escalated++;
        }

        $level2 = Ticket::where('status', '!=', 'closed')
            ->where('is_escalated', true)
            ->where('escalation_level', 1)
            ->where('escalated_at', '<=', now()->subHours((int) config('unify.ticket_escalation_hours', 48)))
            ->get();

        foreach ($level2 as $ticket) {
            $ticket->update([
                'escalation_level' => 2,
                'escalated_at' => now(),
            ]);
            $this->notify($ticket, ['admin', 'owner']);
            $escalated++;
        }

        $this->info($escalated . ' tickets escalated.');
    }

    private function notify(Ticket $ticket, array $roles, ?string $explicitUserId = null): void
    {
        $notifyUserId = $explicitUserId
            ?? \App\Models\User::whereIn('role', $roles)->value('id')
            ?? \App\Models\User::value('id');

        if (! $notifyUserId) {
            return;
        }

        Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $notifyUserId,
            'type' => 'ticket_escalated',
            'title' => 'تیکت escalate شد',
            'body' => $ticket->subject,
            'priority' => 'high',
            'created_at' => now(),
        ]);
    }
}