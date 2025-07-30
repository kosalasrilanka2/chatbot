<?php

use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agent dashboard displays agent profile with skills', function () {
    // Create user and agent
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create([
        'email' => 'agent@test.com',
        'name' => 'Test Agent',
        'status' => 'online'
    ]);

    // Create skills
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'SI']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'HR']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'domain', 'skill_code' => 'FINANCE']);

    $response = $this->actingAs($user)->get('/agent');

    $response->assertStatus(200);
    $response->assertSee('Test Agent');
    $response->assertSee('agent@test.com');
    $response->assertSee('English');
    $response->assertSee('Sinhala');
    $response->assertSee('HR');
    $response->assertSee('FINANCE');
});

test('agent dashboard redirects when no agent profile found', function () {
    $user = User::factory()->create(['email' => 'nonagent@test.com']);

    $response = $this->actingAs($user)->get('/agent');

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('error', 'Agent profile not found. Please contact administrator.');
});

test('agent can update status', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create([
        'email' => 'agent@test.com',
        'status' => 'offline'
    ]);

    $statuses = ['online', 'busy', 'offline'];

    foreach ($statuses as $status) {
        $response = $this->actingAs($user)
            ->postJson('/agent/status', ['status' => $status]);

        $response->assertStatus(200);
        $response->assertJson(['agent_status' => $status]);
        
        expect($agent->fresh()->status)->toBe($status);
    }
});

test('agent status update validation', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com']);

    // Test invalid status
    $response = $this->actingAs($user)
        ->postJson('/agent/status', ['status' => 'invalid']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status']);
});

test('agent can view assigned conversations', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com']);
    $customer = User::factory()->create();

    // Create conversations with different statuses
    $activeConv = Conversation::factory()->create([
        'agent_id' => $agent->id,
        'user_id' => $customer->id,
        'status' => 'active',
        'title' => 'Active Chat'
    ]);

    $closedConv = Conversation::factory()->create([
        'agent_id' => $agent->id,
        'user_id' => $customer->id,
        'status' => 'closed',
        'title' => 'Closed Chat'
    ]);

    $waitingConv = Conversation::factory()->create([
        'agent_id' => null,
        'user_id' => $customer->id,
        'status' => 'waiting',
        'title' => 'Waiting Chat'
    ]);

    $response = $this->actingAs($user)->getJson('/agent/conversations');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'assigned_conversations',
        'waiting_conversations',
        'stats'
    ]);

    // Should include both active and closed conversations
    $assignedConversations = $response->json('assigned_conversations');
    expect($assignedConversations)->toHaveCount(2);

    // Should include waiting conversations
    $waitingConversations = $response->json('waiting_conversations');
    expect($waitingConversations)->toHaveCount(1);
});

test('agent can assign waiting conversation', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com']);
    $customer = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'agent_id' => null,
        'user_id' => $customer->id,
        'status' => 'waiting'
    ]);

    $response = $this->actingAs($user)
        ->postJson("/agent/conversations/{$conversation->id}/assign");

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Conversation assigned successfully']);

    expect($conversation->fresh())
        ->agent_id->toBe($agent->id)
        ->status->toBe('active');
});

test('agent can send messages', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com']);
    $customer = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'agent_id' => $agent->id,
        'user_id' => $customer->id,
        'status' => 'active'
    ]);

    $messageContent = 'Hello, how can I help you?';

    $response = $this->actingAs($user)
        ->postJson('/agent/messages', [
            'conversation_id' => $conversation->id,
            'content' => $messageContent
        ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Message sent successfully']);

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'content' => $messageContent,
        'sender_type' => 'agent',
        'sender_id' => $agent->id
    ]);
});

test('agent cannot send message to closed conversation', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com']);
    $customer = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'agent_id' => $agent->id,
        'user_id' => $customer->id,
        'status' => 'closed'
    ]);

    $response = $this->actingAs($user)
        ->postJson('/agent/messages', [
            'conversation_id' => $conversation->id,
            'content' => 'This should fail'
        ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Cannot send messages to closed conversations']);
});

test('agent can get unread message count', function () {
    $user = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com']);
    $customer = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'agent_id' => $agent->id,
        'user_id' => $customer->id,
        'status' => 'active'
    ]);

    // Create unread messages from user
    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'sender_id' => $customer->id,
        'is_read' => false
    ]);

    // Create read message
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'sender_id' => $customer->id,
        'is_read' => true
    ]);

    $response = $this->actingAs($user)->getJson('/agent/unread-count');

    $response->assertStatus(200);
    $response->assertJson(['unread_count' => 3]);
});

test('agent cannot access other agent conversations', function () {
    $user1 = User::factory()->create(['email' => 'agent1@test.com']);
    $agent1 = Agent::factory()->create(['email' => 'agent1@test.com']);
    
    $user2 = User::factory()->create(['email' => 'agent2@test.com']);
    $agent2 = Agent::factory()->create(['email' => 'agent2@test.com']);
    
    $customer = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'agent_id' => $agent2->id,
        'user_id' => $customer->id,
        'status' => 'active'
    ]);

    // Agent1 tries to access Agent2's conversation
    $response = $this->actingAs($user1)
        ->getJson("/agent/conversations/{$conversation->id}");

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Unauthorized']);
});
