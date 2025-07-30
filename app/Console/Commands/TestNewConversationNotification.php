<?php

namespace App\Console\Commands;

use App\Events\NewConversationEvent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Console\Command;

class TestNewConversationNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:new-conversation-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the real-time new conversation notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing New Conversation Real-time Notification System');
        $this->newLine();

        // Get a test user
        $user = User::first();
        if (!$user) {
            $this->error('❌ No user found. Please create a user first.');
            return 1;
        }

        $this->info("📝 Creating test conversation for user: {$user->name}");

        // Create a test conversation
        $conversation = Conversation::create([
            'title' => 'Test Conversation - ' . now()->format('H:i:s'),
            'user_id' => $user->id,
            'status' => 'waiting',
            'preferred_language' => 'EN',
            'preferred_domain' => 'IT',
            'last_activity' => now()
        ]);

        $this->info("✅ Created conversation ID: {$conversation->id}");

        // Broadcast the event
        $this->info('📡 Broadcasting new conversation event...');
        broadcast(new NewConversationEvent($conversation->fresh()));

        $this->info('✅ New conversation event broadcasted successfully!');
        $this->newLine();
        
        $this->info('🎯 What to expect:');
        $this->info('1. Agents logged into the dashboard should see a notification');
        $this->info('2. The conversations list should update automatically');
        $this->info('3. Check browser console for: "🔔 New conversation created"');
        $this->newLine();
        
        $this->info('🌐 Open these URLs to test:');
        $this->info('• Agent Dashboard: http://localhost:8000/agent');
        $this->info('• Admin Dashboard: http://localhost:8000/admin');
        
        return 0;
    }
}
