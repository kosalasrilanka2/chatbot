<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'conversation_id',
        'content',
        'sender_type',
        'sender_id',
        'is_read',
        'read_at',
        'metadata'
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'metadata' => 'json',
        'is_read' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'sender_id');
    }

    public function getSenderAttribute()
    {
        if ($this->sender_type === 'agent') {
            $agent = Agent::find($this->sender_id);
            return $agent ? $agent->name : 'Agent';
        } elseif ($this->sender_type === 'user') {
            $user = User::find($this->sender_id);
            return $user ? $user->name : 'User';
        }
        
        return 'System';
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }
}
