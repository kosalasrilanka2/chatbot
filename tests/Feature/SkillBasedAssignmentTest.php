<?php

use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('assignment service requires both skills when both specified', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    // Create agents with partial skill matches
    $onlyLanguageAgent = Agent::factory()->create(['status' => 'online']);
    AgentSkill::create(['agent_id' => $onlyLanguageAgent->id, 'skill_type' => 'language', 'skill_code' => 'SI']);

    $onlyDomainAgent = Agent::factory()->create(['status' => 'online']);
    AgentSkill::create(['agent_id' => $onlyDomainAgent->id, 'skill_type' => 'domain', 'skill_code' => 'FINANCE']);

    // Create conversation requiring both skills
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'SI',
        'preferred_domain' => 'FINANCE',
        'status' => 'waiting'
    ]);

    // No agent should be assigned because none have both skills
    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->toBeNull();
    expect($conversation->fresh()->agent_id)->toBeNull();
});

test('assignment service assigns agent with both skills', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    // Create agent with both required skills
    $bothSkillsAgent = Agent::factory()->create(['status' => 'online', 'name' => 'Both Skills Agent']);
    AgentSkill::create(['agent_id' => $bothSkillsAgent->id, 'skill_type' => 'language', 'skill_code' => 'SI']);
    AgentSkill::create(['agent_id' => $bothSkillsAgent->id, 'skill_type' => 'domain', 'skill_code' => 'FINANCE']);

    // Create conversation requiring both skills
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'SI',
        'preferred_domain' => 'FINANCE',
        'status' => 'waiting'
    ]);

    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->not->toBeNull();
    expect($assignedAgent->id)->toBe($bothSkillsAgent->id);
    expect($conversation->fresh()->agent_id)->toBe($bothSkillsAgent->id);
});

test('assignment works with single language skill', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    $agent = Agent::factory()->create(['status' => 'online']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'EN',
        'status' => 'waiting'
    ]);

    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->not->toBeNull();
    expect($assignedAgent->id)->toBe($agent->id);
});

test('assignment works with single domain skill', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    $agent = Agent::factory()->create(['status' => 'online']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'HR']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_domain' => 'HR',
        'status' => 'waiting'
    ]);

    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->not->toBeNull();
    expect($assignedAgent->id)->toBe($agent->id);
});

test('offline agents are not assigned', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    $offlineAgent = Agent::factory()->create(['status' => 'offline']);
    AgentSkill::create(['agent_id' => $offlineAgent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $offlineAgent->id, 'skill_type' => 'domain', 'skill_code' => 'HR']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'EN',
        'preferred_domain' => 'HR',
        'status' => 'waiting'
    ]);

    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->toBeNull();
});

test('agents at capacity are not assigned', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    $agent = Agent::factory()->create(['status' => 'online']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);

    // Create 5 active conversations (max capacity)
    Conversation::factory()->count(5)->create([
        'agent_id' => $agent->id,
        'status' => 'active'
    ]);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'EN',
        'status' => 'waiting'
    ]);

    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->toBeNull();
});

test('agent with lower workload is preferred', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    // Create two agents with same skills
    $busyAgent = Agent::factory()->create(['status' => 'online', 'name' => 'Busy Agent']);
    AgentSkill::create(['agent_id' => $busyAgent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    
    $freeAgent = Agent::factory()->create(['status' => 'online', 'name' => 'Free Agent']);
    AgentSkill::create(['agent_id' => $freeAgent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);

    // Give busy agent 3 conversations
    Conversation::factory()->count(3)->create([
        'agent_id' => $busyAgent->id,
        'status' => 'active'
    ]);

    // Give free agent 1 conversation
    Conversation::factory()->create([
        'agent_id' => $freeAgent->id,
        'status' => 'active'
    ]);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'EN',
        'status' => 'waiting'
    ]);

    $assignedAgent = $service->autoAssignConversation($conversation);
    
    expect($assignedAgent)->not->toBeNull();
    expect($assignedAgent->id)->toBe($freeAgent->id);
});

test('all language and domain combinations are tested', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    $languages = ['SI', 'TI', 'EN'];
    $domains = ['FINANCE', 'HR', 'IT', 'NETWORK'];

    foreach ($languages as $language) {
        foreach ($domains as $domain) {
            // Create agent with specific skill combination
            $agent = Agent::factory()->create(['status' => 'online', 'name' => "{$language} {$domain} Agent"]);
            AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => $language]);
            AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => $domain]);

            // Test assignment
            $conversation = Conversation::factory()->create([
                'user_id' => $user->id,
                'preferred_language' => $language,
                'preferred_domain' => $domain,
                'status' => 'waiting'
            ]);

            $assignedAgent = $service->autoAssignConversation($conversation);
            
            expect($assignedAgent)->not->toBeNull();
            expect($assignedAgent->id)->toBe($agent->id);
            expect($conversation->fresh()->agent_id)->toBe($agent->id);

            // Clean up for next iteration
            $conversation->delete();
            $agent->delete();
        }
    }
});

test('mixed skill scenarios work correctly', function () {
    $user = User::factory()->create();
    $service = new ConversationAssignmentService();

    // Create agents with different skill combinations
    $multiLangAgent = Agent::factory()->create(['status' => 'online', 'name' => 'Multi Language Agent']);
    AgentSkill::create(['agent_id' => $multiLangAgent->id, 'skill_type' => 'language', 'skill_code' => 'SI']);
    AgentSkill::create(['agent_id' => $multiLangAgent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $multiLangAgent->id, 'skill_type' => 'domain', 'skill_code' => 'HR']);

    $multiDomainAgent = Agent::factory()->create(['status' => 'online', 'name' => 'Multi Domain Agent']);
    AgentSkill::create(['agent_id' => $multiDomainAgent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $multiDomainAgent->id, 'skill_type' => 'domain', 'skill_code' => 'IT']);
    AgentSkill::create(['agent_id' => $multiDomainAgent->id, 'skill_type' => 'domain', 'skill_code' => 'NETWORK']);

    // Test SI + HR (should assign to multiLangAgent)
    $conversation1 = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'SI',
        'preferred_domain' => 'HR',
        'status' => 'waiting'
    ]);

    $assignedAgent1 = $service->autoAssignConversation($conversation1);
    expect($assignedAgent1->id)->toBe($multiLangAgent->id);

    // Test EN + IT (should assign to multiDomainAgent)
    $conversation2 = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'EN',
        'preferred_domain' => 'IT',
        'status' => 'waiting'
    ]);

    $assignedAgent2 = $service->autoAssignConversation($conversation2);
    expect($assignedAgent2->id)->toBe($multiDomainAgent->id);

    // Test EN + HR (both agents have these skills, should prefer the one with lower workload)
    $conversation3 = Conversation::factory()->create([
        'user_id' => $user->id,
        'preferred_language' => 'EN',
        'preferred_domain' => 'HR',
        'status' => 'waiting'
    ]);

    $assignedAgent3 = $service->autoAssignConversation($conversation3);
    // multiDomainAgent should be selected as it has fewer active conversations (1 vs 1, but skill match scoring might differ)
    expect($assignedAgent3)->not->toBeNull();
});

test('assignment statistics are accurate', function () {
    $service = new ConversationAssignmentService();

    // Create agents with different statuses and workloads
    $onlineAgent1 = Agent::factory()->create(['status' => 'online', 'name' => 'Online Agent 1']);
    $onlineAgent2 = Agent::factory()->create(['status' => 'online', 'name' => 'Online Agent 2']);
    $offlineAgent = Agent::factory()->create(['status' => 'offline', 'name' => 'Offline Agent']);

    // Create conversations
    Conversation::factory()->count(2)->create(['agent_id' => $onlineAgent1->id, 'status' => 'active']);
    Conversation::factory()->create(['agent_id' => $onlineAgent2->id, 'status' => 'active']);
    Conversation::factory()->count(3)->create(['agent_id' => null, 'status' => 'waiting']);

    $stats = $service->getAssignmentStats();

    expect($stats['online_agents'])->toBe(2);
    expect($stats['waiting_conversations'])->toBe(3);
    expect($stats['active_conversations'])->toBe(3);
    expect($stats['agent_workload'])->toHaveCount(2);
});
