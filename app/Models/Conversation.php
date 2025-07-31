<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'user_id',
        'agent_id',
        'status',
        'last_activity',
        'preferred_language',
        'preferred_domain',
        'priority',
        'skill_requirements',
        'language_match_score',
        'domain_match_score',
        'unread_count'
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'skill_requirements' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->messages()->latest();
    }

    public function unreadMessages()
    {
        return $this->messages()->where('is_read', false);
    }

    /**
     * Get unread messages count for the agent
     */
    public function getUnreadCountForAgent()
    {
        return $this->messages()
            ->where('is_read', false)
            ->where('sender_type', 'user') // Only count user messages as unread for agents
            ->count();
    }

    /**
     * Update the unread count in the conversation
     */
    public function updateUnreadCount()
    {
        $count = $this->getUnreadCountForAgent();
        $this->update(['unread_count' => $count]);
        return $count;
    }

    /**
     * Mark all messages as read and reset unread count
     */
    public function markAllAsRead()
    {
        $this->messages()
            ->where('is_read', false)
            ->where('sender_type', 'user')
            ->update(['is_read' => true, 'read_at' => now()]);
        
        $this->update(['unread_count' => 0]);
    }
}
