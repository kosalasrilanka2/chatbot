<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'name', 
        'email', 
        'status', 
        'avatar', 
        'last_seen'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['online', 'busy']);
    }
}
