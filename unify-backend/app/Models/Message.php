<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'messages';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'sender_id', 'recipient_id', 'specification_id', 'subject', 'body',
        'sent_at', 'is_edited', 'edited_at', 'is_deleted', 'deleted_at',
        'parent_message_id', 'priority',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function specification(): BelongsTo
    {
        return $this->belongsTo(CourseSpecification::class, 'specification_id');
    }

    public function readStatus(): HasMany
    {
        return $this->hasMany(MessageReadStatus::class, 'message_id');
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    /**
     * Broadcast messages have no single recipient (fan-out via specification_id).
     */
    public function isBroadcast(): bool
    {
        return $this->recipient_id === null && $this->specification_id !== null;
    }
}
