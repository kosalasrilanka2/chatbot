<?php

namespace App\Console\Commands;

use App\Events\NewMessageEvent;
use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class TestRealTimeFlow extends Command
{
    protected $signature = 'chat:test-realtime';
    protected $description = 'Test real-time message flow with detailed channel info';

    public function handle()
    {
        $this->info('🔄 Testing Real-Time Message Flow...');
        $this->newLine();

        // Get test user and agent
        $user = User::where('email', 'user@example.com')->first();
        $agent = Agent::where('email', 'agent@example.com')->first();

        if (!$user || !$agent) {
            $this->error('❌ Test user or agent not found');
            $this->info('Creating test users...');
            
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'user@example.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now()
                ]);
                $this->info("✅ Created user: {$user->email}");
            }
            
            if (!$agent) {
                $agent = Agent::create([
                    'name' => 'Test Agent',
                    'email' => 'agent@example.com',
                    'status' => 'online',
                    'last_seen' => now()
                ]);
                $this->info("✅ Created agent: {$agent->email}");
            }
        }

        $this->info("👤 User: {$user->name} (ID: {$user->id})");
        $this->info("🤖 Agent: {$agent->name} (ID: {$agent->id})");

        // Find or create conversation
        $conversation = Conversation::where('user_id', $user->id)->first();
        if (!$conversation) {
            $conversation = Conversation::create([
                'title' => 'Real-Time Test Conversation',
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'status' => 'active',
                'last_activity' => now()
            ]);
            $this->info("💬 Created new conversation ID: {$conversation->id}");
        } else {
            $this->info("💬 Using existing conversation ID: {$conversation->id}");
        }

        $this->newLine();
        $this->info('📡 Broadcasting Test Messages...');

        // Test User Message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'User test message at ' . now()->format('H:i:s'),
            'sender_type' => 'user',
            'sender_id' => $user->id,
        ]);

        $this->info("📤 User message created (ID: {$userMessage->id})");
        $this->info("   Content: {$userMessage->content}");

        try {
            broadcast(new NewMessageEvent($userMessage));
            $this->info("✅ User message broadcast successful");
        } catch (\Exception $e) {
            $this->error("❌ User message broadcast failed: {$e->getMessage()}");
        }

        sleep(1);

        // Test Agent Message
        $agentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Agent response at ' . now()->format('H:i:s'),
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
        ]);

        $this->info("📤 Agent message created (ID: {$agentMessage->id})");
        $this->info("   Content: {$agentMessage->content}");

        try {
            broadcast(new NewMessageEvent($agentMessage));
            $this->info("✅ Agent message broadcast successful");
        } catch (\Exception $e) {
            $this->error("❌ Agent message broadcast failed: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('📊 Channel Broadcasting Information:');
        $this->info("   🔒 conversation.{$conversation->id} - Both user and agent should subscribe");
        $this->info("   🔒 agent.{$agent->id} - Agent notifications");

        $this->newLine();
        $this->info('🔍 Channel Authorization Test:');
        
        // Test channel authorization
        $this->info("   User {$user->id} can access conversation.{$conversation->id}: " . 
                   ($conversation->user_id === $user->id ? 'YES' : 'NO'));
        
        $this->info("   Agent {$agent->id} can access conversation.{$conversation->id}: " . 
                   ($conversation->agent_id === $agent->id ? 'YES' : 'NO'));

        $this->newLine();
        $this->info('🎯 Testing Instructions:');
        $this->info('1. Open user chat: http://localhost:8000/chat');
        $this->info('2. Open agent dashboard: http://localhost:8000/agent/dashboard');
        $this->info('3. Agent should click on the conversation to view it');
        $this->info('4. Both should see the test messages above');
        $this->info('5. Try sending new messages from both interfaces');

        return 0;
    }
}
