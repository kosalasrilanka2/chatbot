<?php

namespace App\Console\Commands;

use App\Events\NewMessageEvent;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Console\Command;

class TestBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test broadcasting system with a sample message';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 Testing Broadcasting System...');
        
        // Get the latest conversation
        $conversation = Conversation::latest()->first();
        
        if (!$conversation) {
            $this->error('❌ No conversations found. Please create a conversation first.');
            return 1;
        }
        
        $this->info("📡 Using conversation ID: {$conversation->id}");
        
        // Create a test message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'This is a test message broadcast at ' . now()->format('H:i:s'),
            'sender_type' => 'system',
            'sender_id' => null,
        ]);
        
        $this->info("💬 Created test message ID: {$message->id}");
        
        try {
            // Broadcast the message
            broadcast(new NewMessageEvent($message))->toOthers();
            
            $this->info('✅ Message broadcast successfully!');
            $this->info('📺 Check your browser for the real-time message update');
            $this->info('🔍 Also check the Reverb server terminal for connection logs');
            
        } catch (\Exception $e) {
            $this->error('❌ Broadcasting failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
