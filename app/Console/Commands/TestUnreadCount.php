<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Agent;

class TestUnreadCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:unread-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test unread count functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing unread count functionality...');

        // Create a test user and agent
        $user = User::firstOrCreate([
            'email' => 'testuser@example.com'
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password')
        ]);

        $agent = Agent::first();
        if (!$agent) {
            $this->error('No agent found. Please create an agent first.');
            return;
        }

        // Create a conversation
        $conversation = Conversation::create([
            'title' => 'Test Unread Count',
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'status' => 'active',
            'last_activity' => now(),
            'unread_count' => 0
        ]);

        $this->info("Created conversation {$conversation->id}");

        // Add some user messages (should be unread)
        $userMessage1 = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Hello, I need help!',
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'is_read' => false
        ]);

        $userMessage2 = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Are you there?',
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'is_read' => false
        ]);

        $this->info('Added 2 user messages (unread)');

        // Check unread count
        $unreadCount = $conversation->recalculateUnreadCount();
        $this->info("Unread count after user messages: {$unreadCount}");

        // Simulate agent replying
        $agentMessage = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Hello! How can I help you?',
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'is_read' => false
        ]);

        // Mark user messages as read (simulating what happens when agent replies)
        $conversation->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $conversation->update(['unread_count' => 0]);

        $this->info('Agent replied and marked user messages as read');

        // Check unread count again
        $unreadCount = $conversation->recalculateUnreadCount();
        $this->info("Unread count after agent reply: {$unreadCount}");

        // Add another user message
        $userMessage3 = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Thank you!',
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'is_read' => false
        ]);

        $this->info('Added another user message');

        // Check unread count again
        $unreadCount = $conversation->recalculateUnreadCount();
        $this->info("Unread count after new user message: {$unreadCount}");

        // Clean up
        $conversation->messages()->delete();
        $conversation->delete();
        $user->delete();

        $this->info('✅ Test completed and cleaned up');
    }
}
