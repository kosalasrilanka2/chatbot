<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
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
        'domain_match_score'
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
}
