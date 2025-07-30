<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'skill_type',
        'skill_code',
        'skill_name',
        'proficiency_level'
    ];

    // Skill constants
    const LANGUAGES = [
        'SI' => 'Sinhala',
        'TI' => 'Tamil',
        'EN' => 'English'
    ];

    const DOMAINS = [
        'FINANCE' => 'Finance',
        'HR' => 'Human Resources',
        'IT' => 'Information Technology',
        'NETWORK' => 'Network Support'
    ];

    const SKILL_TYPES = [
        'language' => 'Language',
        'domain' => 'Domain'
    ];

    /**
     * Get the agent that owns this skill
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Scope for language skills
     */
    public function scopeLanguages($query)
    {
        return $query->where('skill_type', 'language');
    }

    /**
     * Scope for domain skills
     */
    public function scopeDomains($query)
    {
        return $query->where('skill_type', 'domain');
    }

    /**
     * Get all available languages
     */
    public static function getAvailableLanguages()
    {
        return self::LANGUAGES;
    }

    /**
     * Get all available domains
     */
    public static function getAvailableDomains()
    {
        return self::DOMAINS;
    }

    /**
     * Get skill display name
     */
    public function getDisplayNameAttribute()
    {
        if ($this->skill_type === 'language') {
            return self::LANGUAGES[$this->skill_code] ?? $this->skill_code;
        } else {
            return self::DOMAINS[$this->skill_code] ?? $this->skill_code;
        }
    }
}
