<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;

class CreateTestConversation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversation:create-test {--with-messages : Add test messages to the conversation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test conversation for debugging purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::first();
        if (!$user) {
            $this->info('No users found. Creating a test user...');
            $user = User::create([
                'name' => 'Test User ' . now()->format('Hi'),
                'email' => 'test' . now()->timestamp . '@example.com',
                'password' => bcrypt('password')
            ]);
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'status' => 'waiting',
            'last_activity' => now()
        ]);

        $this->info("Created test conversation {$conversation->id}:");
        $this->line("- User: {$user->name} ({$user->email})");
        $this->line("- Agent ID: " . ($conversation->agent_id ?: 'null'));
        $this->line("- Status: {$conversation->status}");

        if ($this->option('with-messages')) {
            // Add some test messages
            Message::create([
                'conversation_id' => $conversation->id,
                'content' => 'Hello, I need help with my account.',
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'sender_name' => $user->name
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'content' => 'Can someone please assist me?',
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'sender_name' => $user->name
            ]);

            // Update unread count
            $conversation->update(['unread_count' => 2]);

            $this->info("Added 2 test messages to the conversation");
        }

        $this->info("This conversation should appear in the waiting list for agents to pick up.");
        
        return 0;
    }
}
