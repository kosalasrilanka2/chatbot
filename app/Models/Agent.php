<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
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

    /**
     * Agent skills relationship
     */
    public function skills(): HasMany
    {
        return $this->hasMany(AgentSkill::class);
    }

    /**
     * Get language skills
     */
    public function languageSkills()
    {
        return $this->skills()->where('skill_type', 'language');
    }

    /**
     * Get domain skills
     */
    public function domainSkills()
    {
        return $this->skills()->where('skill_type', 'domain');
    }

    /**
     * Check if agent has specific language skill
     */
    public function hasLanguageSkill($languageCode)
    {
        return $this->skills()
            ->where('skill_type', 'language')
            ->where('skill_code', $languageCode)
            ->exists();
    }

    /**
     * Check if agent has specific domain skill
     */
    public function hasDomainSkill($domainCode)
    {
        return $this->skills()
            ->where('skill_type', 'domain')
            ->where('skill_code', $domainCode)
            ->exists();
    }

    /**
     * Get skill match score for conversation requirements
     */
    public function getSkillMatchScore($languageCode = null, $domainCode = null)
    {
        $score = 0;
        
        if ($languageCode && $this->hasLanguageSkill($languageCode)) {
            $languageSkill = $this->skills()
                ->where('skill_type', 'language')
                ->where('skill_code', $languageCode)
                ->first();
            $score += $languageSkill ? $languageSkill->proficiency_level * 20 : 0;
        }
        
        if ($domainCode && $this->hasDomainSkill($domainCode)) {
            $domainSkill = $this->skills()
                ->where('skill_type', 'domain')
                ->where('skill_code', $domainCode)
                ->first();
            $score += $domainSkill ? $domainSkill->proficiency_level * 15 : 0;
        }
        
        return $score;
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
