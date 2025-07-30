<?php

use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agent has skills relationship', function () {
    $agent = Agent::factory()->create();
    
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'HR', 'skill_name' => 'Human Resources']);

    $agent->load('skills');
    
    expect($agent->skills)->toHaveCount(2);
    expect($agent->skills->pluck('skill_code')->toArray())->toContain('EN', 'HR');
});

test('agent has conversations relationship', function () {
    $agent = Agent::factory()->create();
    
    Conversation::factory()->count(3)->create(['agent_id' => $agent->id]);

    $agent->load('conversations');
    
    expect($agent->conversations)->toHaveCount(3);
});

test('agent has language skills', function () {
    $agent = Agent::factory()->create();
    
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'SI', 'skill_name' => 'Sinhala']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'HR', 'skill_name' => 'Human Resources']);

    $languageSkills = $agent->languageSkills;
    
    expect($languageSkills)->toHaveCount(2);
    expect($languageSkills->pluck('skill_code')->toArray())->toContain('EN', 'SI');
    expect($languageSkills->pluck('skill_code')->toArray())->not->toContain('HR');
});

test('agent has domain skills', function () {
    $agent = Agent::factory()->create();
    
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'HR', 'skill_name' => 'Human Resources']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'FINANCE', 'skill_name' => 'Finance']);

    $domainSkills = $agent->domainSkills;
    
    expect($domainSkills)->toHaveCount(2);
    expect($domainSkills->pluck('skill_code')->toArray())->toContain('HR', 'FINANCE');
    expect($domainSkills->pluck('skill_code')->toArray())->not->toContain('EN');
});

test('agent can check if has language skill', function () {
    $agent = Agent::factory()->create();
    
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'SI', 'skill_name' => 'Sinhala']);

    expect($agent->hasLanguageSkill('EN'))->toBe(true);
    expect($agent->hasLanguageSkill('SI'))->toBe(true);
    expect($agent->hasLanguageSkill('TI'))->toBe(false);
    expect($agent->hasLanguageSkill('FR'))->toBe(false);
});

test('agent can check if has domain skill', function () {
    $agent = Agent::factory()->create();
    
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'HR', 'skill_name' => 'Human Resources']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'FINANCE', 'skill_name' => 'Finance']);

    expect($agent->hasDomainSkill('HR'))->toBe(true);
    expect($agent->hasDomainSkill('FINANCE'))->toBe(true);
    expect($agent->hasDomainSkill('IT'))->toBe(false);
    expect($agent->hasDomainSkill('NETWORK'))->toBe(false);
});

test('agent calculates skill match score correctly', function () {
    $agent = Agent::factory()->create();
    
    // Add skills with proficiency levels
    AgentSkill::create([
        'agent_id' => $agent->id, 
        'skill_type' => 'language', 
        'skill_code' => 'EN',
        'skill_name' => 'English',
        'proficiency_level' => 5
    ]);
    AgentSkill::create([
        'agent_id' => $agent->id, 
        'skill_type' => 'domain', 
        'skill_code' => 'HR',
        'skill_name' => 'Human Resources',
        'proficiency_level' => 4
    ]);

    // Test perfect match
    $score = $agent->getSkillMatchScore('EN', 'HR');
    expect($score)->toBe(160); // (5 * 20) + (4 * 15) = 100 + 60

    // Test partial match (language only)
    $score = $agent->getSkillMatchScore('EN', 'IT');
    expect($score)->toBe(100); // 5 * 20 + 0

    // Test partial match (domain only)
    $score = $agent->getSkillMatchScore('SI', 'HR');
    expect($score)->toBe(60); // 0 + (4 * 15)

    // Test no match
    $score = $agent->getSkillMatchScore('SI', 'IT');
    expect($score)->toBe(0); // 0 + 0
});

test('agent status updates correctly', function () {
    $agent = Agent::factory()->create(['status' => 'offline']);

    expect($agent->status)->toBe('offline');

    $agent->update(['status' => 'online']);
    expect($agent->fresh()->status)->toBe('online');

    $agent->update(['status' => 'busy']);
    expect($agent->fresh()->status)->toBe('busy');
});

test('agent last seen updates', function () {
    $agent = Agent::factory()->create(['last_seen' => now()->subHour()]);

    $oldLastSeen = $agent->last_seen;
    
    $agent->update(['last_seen' => now()]);
    
    expect($agent->fresh()->last_seen)->toBeGreaterThan($oldLastSeen);
});

test('agent factory creates valid agent', function () {
    $agent = Agent::factory()->create();

    expect($agent->name)->not->toBeEmpty();
    expect($agent->email)->toContain('@');
    expect($agent->status)->toBeIn(['online', 'offline', 'busy']);
});

test('agent fillable attributes work correctly', function () {
    $data = [
        'name' => 'Test Agent',
        'email' => 'test@agent.com',
        'status' => 'online'
    ];

    $agent = Agent::create($data);

    expect($agent->name)->toBe('Test Agent');
    expect($agent->email)->toBe('test@agent.com');
    expect($agent->status)->toBe('online');
});
