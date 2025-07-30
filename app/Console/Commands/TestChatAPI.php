<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class TestChatAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test chat functionality and data integrity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Chat API and Data...');
        $this->newLine();

        // Test 1: Check if users exist
        $this->info('1. Checking Users...');
        $users = User::all();
        $this->table(['ID', 'Name', 'Email'], $users->map(fn($u) => [$u->id, $u->name, $u->email]));

        // Test 2: Check if agents exist
        $this->info('2. Checking Agents...');
        $agents = Agent::all();
        $this->table(['ID', 'Name', 'Email', 'Status'], $agents->map(fn($a) => [$a->id, $a->name, $a->email, $a->status]));

        // Test 3: Check conversations
        $this->info('3. Checking Conversations...');
        $conversations = Conversation::with(['user', 'agent'])->get();
        if ($conversations->count() > 0) {
            $this->table(['ID', 'Title', 'User', 'Agent', 'Status'], 
                $conversations->map(fn($c) => [
                    $c->id, 
                    $c->title, 
                    $c->user->name ?? 'N/A', 
                    $c->agent->name ?? 'Unassigned', 
                    $c->status
                ])
            );
        } else {
            $this->warn('No conversations found.');
        }

        // Test 4: Check messages
        $this->info('4. Checking Messages...');
        $messages = Message::with('conversation')->get();
        if ($messages->count() > 0) {
            $this->table(['ID', 'Conversation', 'Sender Type', 'Sender ID', 'Content'], 
                $messages->map(fn($m) => [
                    $m->id, 
                    "Conv #{$m->conversation_id}", 
                    $m->sender_type, 
                    $m->sender_id,
                    substr($m->content, 0, 30) . '...'
                ])
            );
        } else {
            $this->warn('No messages found.');
        }

        // Test 5: Test message sender resolution
        $this->info('5. Testing Message Sender Resolution...');
        foreach ($messages as $message) {
            $senderName = $message->getSenderAttribute();
            $this->line("Message {$message->id}: {$message->sender_type} -> {$senderName}");
        }

        // Test 6: Create a test conversation and message
        $this->info('6. Creating Test Data...');
        $testUser = User::first();
        
        if ($testUser) {
            $testConv = Conversation::create([
                'title' => 'Test Conversation',
                'user_id' => $testUser->id,
                'status' => 'active',
                'last_activity' => now()
            ]);

            $testMessage = Message::create([
                'conversation_id' => $testConv->id,
                'content' => 'Test message from command',
                'sender_type' => 'user',
                'sender_id' => $testUser->id,
            ]);

            $this->info("✅ Created test conversation #{$testConv->id} and message #{$testMessage->id}");
            $this->info("✅ Sender name resolution: " . $testMessage->getSenderAttribute());
        } else {
            $this->error('❌ No users found for testing');
        }

        $this->newLine();
        $this->info('✅ Chat API test completed!');
        
        return 0;
    }
}
