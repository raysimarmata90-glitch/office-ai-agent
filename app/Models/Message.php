<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'content',
        'step_number',
        'metadata',
        'is_read',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Message $message): void {
            $message->syncChatHistory();
        });

        static::updated(function (Message $message): void {
            if ($message->wasChanged(['content', 'metadata', 'step_number'])) {
                $message->syncChatHistory();
            }
        });
    }

    public function syncChatHistory(): void
    {
        $this->loadMissing('conversation');

        ChatHistory::updateOrCreate(
            ['message_id' => $this->id],
            [
                'conversation_id' => $this->conversation_id,
                'user_id' => $this->conversation->user_id,
                'sender_type' => $this->getAttribute('sender_type'),
                'content' => $this->getAttribute('content'),
                'step_number' => $this->getAttribute('step_number'),
                'metadata' => $this->getAttribute('metadata'),
            ]
        );
    }

    public function getContentAttribute(?string $value): string
    {
        if ($value === null || $this->sender_type !== 'ai') {
            return $value ?? '';
        }

        $value = preg_replace('/```(?:\w+)?\s*|```/i', '', $value);
        $value = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $value);
        $value = preg_replace('/^\s*>\s?/m', '', $value);
        $value = preg_replace('/^\s*[-*+]\s+/m', '', $value);
        $value = preg_replace('/^\s*\d+[.)]\s+/m', '', $value);
        $value = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $value);
        $value = preg_replace('/\*\*([^*]+)\*\*|__([^_]+)__/', '$1$2', $value);
        $value = preg_replace('/(?<!\w)[*_]([^*_]+)[*_](?!\w)/', '$1', $value);
        $value = preg_replace('/`([^`]+)`/', '$1', $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value);

        return trim($value);
    }

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // Helper methods
    public function isFromAI(): bool
    {
        return $this->sender_type === 'ai';
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
