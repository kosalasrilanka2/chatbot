<?php

namespace App\Console\Commands;

use App\Events\ConversationClosedEvent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class TestConversationClosure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:conversation-closure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the real-time conversation closure notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Conversation Closure Real-time Notification System');
        $this->newLine();

        // Get an active conversation to close
        $conversation = Conversation::where('status', 'active')->first();
        
        if (!$conversation) {
            // Try to get a waiting conversation instead
            $conversation = Conversation::where('status', 'waiting')->first();
        }
        
        if (!$conversation) {
            $this->error('❌ No active or waiting conversation found. Please create a conversation first.');
            return 1;
        }

        $this->info("📝 Found conversation ID: {$conversation->id}");
        $this->info("   User: {$conversation->user->name}");
        $this->info("   Status: {$conversation->status}");
        $this->info("   Agent: " . ($conversation->agent ? $conversation->agent->name : 'None'));

        // Update conversation status to closed
        $conversation->update([
            'status' => 'closed',
            'last_activity' => now()
        ]);

        // Create a system message
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Conversation ended by test command at ' . now()->format('Y-m-d H:i:s'),
            'sender_type' => 'system',
            'sender_id' => null,
        ]);

        $this->info('✅ Conversation marked as closed');

        // Broadcast the closure event
        $this->info('📡 Broadcasting conversation closure event...');
        broadcast(new ConversationClosedEvent($conversation->fresh()));

        $this->info('✅ Conversation closure event broadcasted successfully!');
        $this->newLine();
        
        $this->info('🎯 What to expect:');
        $this->info('1. Agents should see a notification about the closure');
        $this->info('2. The conversation should appear grayed out in the list');
        $this->info('3. If agent has this conversation open, it should switch to read-only mode');
        $this->info('4. Check browser console for: "🔒 Conversation closed"');
        $this->newLine();
        
        $this->info('🌐 Open Agent Dashboard to test: http://localhost:8000/agent');
        
        return 0;
    }
}
