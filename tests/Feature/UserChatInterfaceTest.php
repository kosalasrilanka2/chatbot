<?php

use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can access chat interface', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/chat/user');

    $response->assertStatus(200);
    $response->assertSee('Start New Conversation');
});

test('user can create conversation without skills', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'title' => 'Test Conversation'
        ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['conversation']);

    $this->assertDatabaseHas('conversations', [
        'user_id' => $user->id,
        'title' => 'Test Conversation',
        'status' => 'waiting'
    ]);
});

test('user can create conversation with language preference', function () {
    $user = User::factory()->create();

    $languages = ['SI', 'TI', 'EN'];

    foreach ($languages as $language) {
        $response = $this->actingAs($user)
            ->postJson('/conversations', [
                'title' => "Test {$language} Conversation",
                'preferred_language' => $language
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'preferred_language' => $language
        ]);
    }
});

test('user can create conversation with domain preference', function () {
    $user = User::factory()->create();

    $domains = ['FINANCE', 'HR', 'IT', 'NETWORK'];

    foreach ($domains as $domain) {
        $response = $this->actingAs($user)
            ->postJson('/conversations', [
                'title' => "Test {$domain} Conversation",
                'preferred_domain' => $domain
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'preferred_domain' => $domain
        ]);
    }
});

test('user can create conversation with both language and domain preferences', function () {
    $user = User::factory()->create();

    $combinations = [
        ['SI', 'FINANCE'],
        ['EN', 'HR'],
        ['TI', 'IT'],
        ['EN', 'NETWORK']
    ];

    foreach ($combinations as [$language, $domain]) {
        $response = $this->actingAs($user)
            ->postJson('/conversations', [
                'title' => "Test {$language} {$domain} Conversation",
                'preferred_language' => $language,
                'preferred_domain' => $domain
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'preferred_language' => $language,
            'preferred_domain' => $domain
        ]);
    }
});

test('conversation creation validates skill preferences', function () {
    $user = User::factory()->create();

    // Test invalid language
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'preferred_language' => 'INVALID'
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['preferred_language']);

    // Test invalid domain
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'preferred_domain' => 'INVALID'
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['preferred_domain']);
});

test('user can send messages to conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'status' => 'active'
    ]);

    $messageContent = 'Hello, I need help!';

    $response = $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation->id,
            'content' => $messageContent,
            'sender_type' => 'user'
        ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Message sent successfully']);

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'content' => $messageContent,
        'sender_type' => 'user',
        'sender_id' => $user->id
    ]);
});

test('user can close conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'status' => 'active'
    ]);

    $response = $this->actingAs($user)
        ->postJson("/conversations/{$conversation->id}/close");

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    expect($conversation->fresh()->status)->toBe('closed');

    // Should create system message
    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_type' => 'system',
        'content' => 'Conversation ended by user at ' . now()->format('Y-m-d H:i:s')
    ]);
});

test('user cannot close other users conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'user_id' => $user2->id,
        'status' => 'active'
    ]);

    $response = $this->actingAs($user1)
        ->postJson("/conversations/{$conversation->id}/close");

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});

test('user can view their conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create user's conversations
    $userConv1 = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'User Conv 1'
    ]);
    $userConv2 = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'User Conv 2'
    ]);

    // Create other user's conversation
    $otherConv = Conversation::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Other Conv'
    ]);

    $response = $this->actingAs($user)->getJson('/conversations');

    $response->assertStatus(200);
    $conversations = $response->json();

    expect($conversations)->toHaveCount(2);
    expect(collect($conversations)->pluck('id'))->toContain($userConv1->id, $userConv2->id);
    expect(collect($conversations)->pluck('id'))->not->toContain($otherConv->id);
});

test('user can view conversation messages', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'status' => 'active'
    ]);

    // Create messages
    $userMessage = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'sender_id' => $user->id,
        'content' => 'User message'
    ]);

    $agentMessage = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => 'agent',
        'sender_id' => $agent->id,
        'content' => 'Agent response'
    ]);

    $response = $this->actingAs($user)
        ->getJson("/conversations/{$conversation->id}/messages");

    $response->assertStatus(200);
    $messages = $response->json();

    expect($messages)->toHaveCount(2);
    expect(collect($messages)->pluck('content'))->toContain('User message', 'Agent response');
});

test('skill based assignment works correctly', function () {
    $user = User::factory()->create();
    
    // Create agents with specific skills
    $siFinanceAgent = Agent::factory()->create([
        'name' => 'SI Finance Agent',
        'status' => 'online'
    ]);
    AgentSkill::create(['agent_id' => $siFinanceAgent->id, 'skill_type' => 'language', 'skill_code' => 'SI']);
    AgentSkill::create(['agent_id' => $siFinanceAgent->id, 'skill_type' => 'domain', 'skill_code' => 'FINANCE']);

    $enHrAgent = Agent::factory()->create([
        'name' => 'EN HR Agent',
        'status' => 'online'
    ]);
    AgentSkill::create(['agent_id' => $enHrAgent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $enHrAgent->id, 'skill_type' => 'domain', 'skill_code' => 'HR']);

    // Test SI + FINANCE assignment
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'title' => 'SI Finance Help',
            'preferred_language' => 'SI',
            'preferred_domain' => 'FINANCE'
        ]);

    $response->assertStatus(200);
    $conversation = $response->json('conversation');
    
    expect($conversation['agent_id'])->toBe($siFinanceAgent->id);
    expect($response->json('auto_assigned'))->toBe(true);
    expect($response->json('agent_name'))->toBe('SI Finance Agent');

    // Test EN + HR assignment
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'title' => 'EN HR Help',
            'preferred_language' => 'EN',
            'preferred_domain' => 'HR'
        ]);

    $response->assertStatus(200);
    $conversation = $response->json('conversation');
    
    expect($conversation['agent_id'])->toBe($enHrAgent->id);
    expect($response->json('auto_assigned'))->toBe(true);
    expect($response->json('agent_name'))->toBe('EN HR Agent');
});

test('no assignment when no matching skills', function () {
    $user = User::factory()->create();
    
    // Create agent with different skills
    $agent = Agent::factory()->create([
        'name' => 'Different Skills Agent',
        'status' => 'online'
    ]);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'IT']);

    // Request SI + FINANCE (no matching agent)
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'title' => 'SI Finance Help',
            'preferred_language' => 'SI',
            'preferred_domain' => 'FINANCE'
        ]);

    $response->assertStatus(200);
    $conversation = $response->json('conversation');
    
    expect($conversation['agent_id'])->toBeNull();
    expect($response->json('auto_assigned'))->toBe(false);
    expect($response->json('agent_name'))->toBeNull();
});
