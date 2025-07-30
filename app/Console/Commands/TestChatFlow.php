<?php

namespace App\Console\Commands;

use App\Events\NewMessageEvent;
use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class TestChatFlow extends Command
{
    protected $signature = 'chat:test-flow';
    protected $description = 'Test complete chat flow between user and agent';

    public function handle()
    {
        $this->info('🧪 Testing Complete Chat Flow...');
        $this->newLine();

        // Get test user and agent
        $user = User::where('email', 'user@example.com')->first();
        $agent = Agent::where('email', 'agent@example.com')->first();

        if (!$user || !$agent) {
            $this->error('❌ Test user or agent not found');
            return 1;
        }

        $this->info("👤 User: {$user->name} (ID: {$user->id})");
        $this->info("🤖 Agent: {$agent->name} (ID: {$agent->id})");

        // Create a conversation
        $conversation = Conversation::create([
            'title' => 'Test Chat Flow',
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'status' => 'active',
            'last_activity' => now()
        ]);

        $this->info("💬 Created conversation ID: {$conversation->id}");

        // Test 1: User sends message
        $this->info('📤 Testing user message...');
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Hello from user at ' . now()->format('H:i:s'),
            'sender_type' => 'user',
            'sender_id' => $user->id,
        ]);

        try {
            broadcast(new NewMessageEvent($userMessage));
            $this->info("✅ User message broadcast - ID: {$userMessage->id}");
        } catch (\Exception $e) {
            $this->error("❌ User message broadcast failed: {$e->getMessage()}");
        }

        sleep(1);

        // Test 2: Agent sends message
        $this->info('📤 Testing agent message...');
        $agentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Hello from agent at ' . now()->format('H:i:s'),
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
        ]);

        try {
            broadcast(new NewMessageEvent($agentMessage));
            $this->info("✅ Agent message broadcast - ID: {$agentMessage->id}");
        } catch (\Exception $e) {
            $this->error("❌ Agent message broadcast failed: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('📊 Broadcast Channels:');
        $this->info("   - conversation.{$conversation->id}");
        $this->info("   - agent.{$agent->id}");

        $this->newLine();
        $this->info('🔍 To test:');
        $this->info('1. Login as user and go to chat');
        $this->info('2. Login as agent in another browser and go to agent dashboard');
        $this->info('3. Check browser consoles for WebSocket messages');

        return 0;
    }
}
