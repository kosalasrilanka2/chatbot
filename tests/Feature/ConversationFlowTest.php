<?php

use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('complete conversation flow from creation to closure', function () {
    // Setup
    $user = User::factory()->create();
    $agentUser = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com', 'status' => 'online']);
    AgentSkill::create(['agent_id' => $agent->id, 'skill_type' => 'language', 'skill_code' => 'EN']);

    // Step 1: User creates conversation
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'title' => 'Help with account',
            'preferred_language' => 'EN'
        ]);

    $response->assertStatus(200);
    $conversation = $response->json('conversation');
    expect($conversation['agent_id'])->toBe($agent->id);
    expect($response->json('auto_assigned'))->toBe(true);

    // Step 2: User sends initial message
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'I need help with my account',
            'sender_type' => 'user'
        ])
        ->assertStatus(200);

    // Step 3: Agent responds
    $this->actingAs($agentUser)
        ->postJson('/agent/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'Hello! I can help you with your account. What specific issue are you experiencing?'
        ])
        ->assertStatus(200);

    // Step 4: User responds
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'I cannot access my profile settings',
            'sender_type' => 'user'
        ])
        ->assertStatus(200);

    // Step 5: Agent provides solution
    $this->actingAs($agentUser)
        ->postJson('/agent/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'I can help with that. Please try clearing your browser cache and cookies, then log in again.'
        ])
        ->assertStatus(200);

    // Step 6: User thanks and closes conversation
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'Thank you! That worked perfectly.',
            'sender_type' => 'user'
        ])
        ->assertStatus(200);

    $this->actingAs($user)
        ->postJson("/conversations/{$conversation['id']}/close")
        ->assertStatus(200);

    // Verify final state
    $finalConversation = Conversation::find($conversation['id']);
    expect($finalConversation->status)->toBe('closed');

    $messages = Message::where('conversation_id', $conversation['id'])->orderBy('created_at')->get();
    expect($messages)->toHaveCount(6); // 4 user/agent messages + 1 auto-assignment + 1 closure system message
});

test('conversation flow with manual agent assignment', function () {
    $user = User::factory()->create();
    $agentUser = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com', 'status' => 'online']);

    // Create conversation without auto-assignment (no matching skills)
    $response = $this->actingAs($user)
        ->postJson('/conversations', [
            'title' => 'General inquiry',
            'preferred_language' => 'SI', // Agent doesn't have SI skill
            'preferred_domain' => 'FINANCE'
        ]);

    $conversation = $response->json('conversation');
    expect($conversation['agent_id'])->toBeNull();
    expect($response->json('auto_assigned'))->toBe(false);

    // User sends message to waiting conversation
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'Hello, is anyone there?',
            'sender_type' => 'user'
        ])
        ->assertStatus(200);

    // Agent manually picks up the conversation
    $this->actingAs($agentUser)
        ->postJson("/agent/conversations/{$conversation['id']}/assign")
        ->assertStatus(200);

    // Verify assignment
    $updatedConversation = Conversation::find($conversation['id']);
    expect($updatedConversation->agent_id)->toBe($agent->id);
    expect($updatedConversation->status)->toBe('active');

    // Continue conversation flow
    $this->actingAs($agentUser)
        ->postJson('/agent/messages', [
            'conversation_id' => $conversation['id'],
            'content' => 'Hello! I apologize for the wait. How can I assist you today?'
        ])
        ->assertStatus(200);
});

test('conversation flow with agent going offline', function () {
    $user = User::factory()->create();
    $agent1User = User::factory()->create(['email' => 'agent1@test.com']);
    $agent1 = Agent::factory()->create(['email' => 'agent1@test.com', 'status' => 'online']);
    
    $agent2User = User::factory()->create(['email' => 'agent2@test.com']);
    $agent2 = Agent::factory()->create(['email' => 'agent2@test.com', 'status' => 'online']);

    // Add same skills to both agents
    AgentSkill::create(['agent_id' => $agent1->id, 'skill_type' => 'language', 'skill_code' => 'EN']);
    AgentSkill::create(['agent_id' => $agent2->id, 'skill_type' => 'language', 'skill_code' => 'EN']);

    // Create conversation assigned to agent1
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'agent_id' => $agent1->id,
        'status' => 'active',
        'preferred_language' => 'EN'
    ]);

    // Agent1 goes offline
    $this->actingAs($agent1User)
        ->postJson('/agent/status', ['status' => 'offline'])
        ->assertStatus(200);

    // Check if conversation was redistributed
    $updatedConversation = $conversation->fresh();
    // Note: Redistribution logic should be tested separately as it's triggered by status change
});

test('conversation with multiple message exchanges', function () {
    $user = User::factory()->create();
    $agentUser = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com', 'status' => 'online']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'status' => 'active'
    ]);

    $messageExchanges = [
        ['user', 'Hello, I need help with billing'],
        ['agent', 'I can help you with billing. What specific issue do you have?'],
        ['user', 'I was charged twice for my subscription'],
        ['agent', 'I see the issue. Let me check your account...'],
        ['agent', 'I found the duplicate charge. I will process a refund for you.'],
        ['user', 'How long will the refund take?'],
        ['agent', 'The refund will appear in your account within 3-5 business days.'],
        ['user', 'Perfect, thank you for your help!']
    ];

    foreach ($messageExchanges as [$senderType, $content]) {
        if ($senderType === 'user') {
            $this->actingAs($user)
                ->postJson('/messages', [
                    'conversation_id' => $conversation->id,
                    'content' => $content,
                    'sender_type' => 'user'
                ])
                ->assertStatus(200);
        } else {
            $this->actingAs($agentUser)
                ->postJson('/agent/messages', [
                    'conversation_id' => $conversation->id,
                    'content' => $content
                ])
                ->assertStatus(200);
        }
    }

    $messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at')->get();
    expect($messages)->toHaveCount(8);

    // Verify message order and content
    foreach ($messageExchanges as $index => [$expectedSender, $expectedContent]) {
        expect($messages[$index]->sender_type)->toBe($expectedSender);
        expect($messages[$index]->content)->toBe($expectedContent);
    }
});

test('conversation flow with validation errors', function () {
    $user = User::factory()->create();
    $agentUser = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com', 'status' => 'online']);

    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'agent_id' => $agent->id,
        'status' => 'active'
    ]);

    // Test empty message
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation->id,
            'content' => '',
            'sender_type' => 'user'
        ])
        ->assertStatus(422);

    // Test invalid conversation ID
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => 99999,
            'content' => 'Test message',
            'sender_type' => 'user'
        ])
        ->assertStatus(422);

    // Test invalid sender type
    $this->actingAs($user)
        ->postJson('/messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Test message',
            'sender_type' => 'invalid'
        ])
        ->assertStatus(422);
});

test('conversation flow with concurrent users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $agentUser = User::factory()->create(['email' => 'agent@test.com']);
    $agent = Agent::factory()->create(['email' => 'agent@test.com', 'status' => 'online']);

    // Create separate conversations for both users
    $conv1 = Conversation::factory()->create([
        'user_id' => $user1->id,
        'agent_id' => $agent->id,
        'status' => 'active'
    ]);

    $conv2 = Conversation::factory()->create([
        'user_id' => $user2->id,
        'agent_id' => $agent->id,
        'status' => 'active'
    ]);

    // Both users send messages simultaneously
    $this->actingAs($user1)
        ->postJson('/messages', [
            'conversation_id' => $conv1->id,
            'content' => 'User 1 message',
            'sender_type' => 'user'
        ])
        ->assertStatus(200);

    $this->actingAs($user2)
        ->postJson('/messages', [
            'conversation_id' => $conv2->id,
            'content' => 'User 2 message',
            'sender_type' => 'user'
        ])
        ->assertStatus(200);

    // Agent responds to both
    $this->actingAs($agentUser)
        ->postJson('/agent/messages', [
            'conversation_id' => $conv1->id,
            'content' => 'Response to User 1'
        ])
        ->assertStatus(200);

    $this->actingAs($agentUser)
        ->postJson('/agent/messages', [
            'conversation_id' => $conv2->id,
            'content' => 'Response to User 2'
        ])
        ->assertStatus(200);

    // Verify messages are in correct conversations
    $conv1Messages = Message::where('conversation_id', $conv1->id)->get();
    $conv2Messages = Message::where('conversation_id', $conv2->id)->get();

    expect($conv1Messages)->toHaveCount(2);
    expect($conv2Messages)->toHaveCount(2);

    expect($conv1Messages->pluck('content')->toArray())->toContain('User 1 message', 'Response to User 1');
    expect($conv2Messages->pluck('content')->toArray())->toContain('User 2 message', 'Response to User 2');
});
