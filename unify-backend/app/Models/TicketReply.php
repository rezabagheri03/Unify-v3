<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    protected $table = 'ticket_replies';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'ticket_id', 'sender_id', 'body', 'attachments', 'sent_at', 'is_staff',
    ];

    protected $casts = [
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'is_staff' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
