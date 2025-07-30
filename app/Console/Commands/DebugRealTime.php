<?php

namespace App\Console\Commands;

use App\Events\NewMessageEvent;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Agent;
use Illuminate\Console\Command;

class DebugRealTime extends Command
{
    protected $signature = 'chat:debug-realtime';
    protected $description = 'Debug real-time messaging with detailed logs';

    public function handle()
    {
        $this->info('🔍 Debugging Real-Time Messaging...');
        $this->newLine();

        // Get a conversation with both user and agent
        $conversation = Conversation::whereNotNull('agent_id')->first();
        
        if (!$conversation) {
            $this->error('❌ No conversation with agent found');
            return 1;
        }

        $this->info("📋 Using Conversation ID: {$conversation->id}");
        $this->info("👤 User ID: {$conversation->user_id}");
        $this->info("🤖 Agent ID: {$conversation->agent_id}");
        $this->newLine();

        // Create a test message from agent
        $agent = Agent::find($conversation->agent_id);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Agent debug message at ' . now()->format('H:i:s'),
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
        ]);

        $this->info("💬 Created message ID: {$message->id}");
        $this->info("📝 Content: {$message->content}");
        $this->info("👤 Sender: {$message->getSenderAttribute()}");

        try {
            $this->info('📡 Broadcasting event...');
            
            // Load the message with conversation relationship
            $message->load('conversation');
            
            $event = new NewMessageEvent($message);
            
            $this->info('📺 Channels that will receive broadcast:');
            foreach ($event->broadcastOn() as $channel) {
                $this->info("   - {$channel->name}");
            }
            
            $this->info('📦 Broadcast data:');
            $broadcastData = $event->broadcastWith();
            $this->info('   ' . json_encode($broadcastData, JSON_PRETTY_PRINT));
            
            $this->info('🎯 Event name: ' . $event->broadcastAs());
            
            // Actually broadcast
            broadcast($event);
            
            $this->info('✅ Broadcast completed!');
            $this->newLine();
            
            $this->info('🔍 What to check:');
            $this->info('1. Open browser console on user chat page');
            $this->info('2. Open browser console on agent dashboard with this conversation open');
            $this->info('3. Look for WebSocket connection messages');
            $this->info('4. Look for message.new event logs');
            $this->info('5. Check if Reverb server shows any connection activity');
            
        } catch (\Exception $e) {
            $this->error('❌ Broadcast failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
