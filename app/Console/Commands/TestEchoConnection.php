<?php

namespace App\Console\Commands;

use App\Events\NewMessageEvent;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Console\Command;

class TestEchoConnection extends Command
{
    protected $signature = 'echo:test-connection';
    protected $description = 'Test Echo connection with countdown and visible message';

    public function handle()
    {
        $this->info('🔍 Testing Echo Connection...');
        $this->newLine();

        $conversation = Conversation::first();
        if (!$conversation) {
            $this->error('❌ No conversation found');
            return 1;
        }

        $this->info("📋 Using conversation ID: {$conversation->id}");
        $this->info("🔗 Visit: http://localhost:8000/echo-debug");
        $this->newLine();

        $this->info('⏰ Sending test message in:');
        for ($i = 5; $i >= 1; $i--) {
            $this->info("   {$i}...");
            sleep(1);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'content' => '🚀 TEST MESSAGE: If you see this in real-time, Echo is working! Time: ' . now()->format('H:i:s'),
            'sender_type' => 'system',
            'sender_id' => null,
        ]);

        $this->info("📤 Sending message ID: {$message->id}");

        try {
            broadcast(new NewMessageEvent($message));
            $this->info('✅ Message sent!');
            $this->info('👀 Check your browser - you should see the message appear WITHOUT refreshing');
        } catch (\Exception $e) {
            $this->error('❌ Failed: ' . $e->getMessage());
        }

        return 0;
    }
}
