<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class CreateSampleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample conversations and messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📝 Creating sample conversations and messages...');

        $user1 = User::where('email', 'user@test.com')->first();
        $user2 = User::where('email', 'john@test.com')->first();
        $agent1 = Agent::where('email', 'agent@test.com')->first();
        $agent2 = Agent::where('email', 'senior@test.com')->first();

        if (!$user1 || !$user2 || !$agent1 || !$agent2) {
            $this->error('❌ Users or agents not found. Please run "php artisan users:create" first.');
            return 1;
        }

        // Create conversation 1: user1 with agent1
        $conversation1 = Conversation::create([
            'title' => 'Account Help Request',
            'user_id' => $user1->id,
            'agent_id' => $agent1->id,
            'status' => 'active',
            'last_activity' => now(),
        ]);

        // Messages for conversation 1
        Message::create([
            'conversation_id' => $conversation1->id,
            'content' => 'Hello! I need help with my account settings.',
            'sender_type' => 'user',
            'sender_id' => $user1->id,
            'is_read' => true,
            'created_at' => now()->subMinutes(10),
        ]);

        Message::create([
            'conversation_id' => $conversation1->id,
            'content' => 'Hi! I\'d be happy to help you with your account. What specific issue are you experiencing?',
            'sender_type' => 'agent',
            'sender_id' => $agent1->id,
            'is_read' => true,
            'created_at' => now()->subMinutes(9),
        ]);

        Message::create([
            'conversation_id' => $conversation1->id,
            'content' => 'I can\'t seem to update my profile information. The save button doesn\'t work.',
            'sender_type' => 'user',
            'sender_id' => $user1->id,
            'is_read' => false,
            'created_at' => now()->subMinutes(8),
        ]);

        // Create conversation 2: user2 with agent2
        $conversation2 = Conversation::create([
            'title' => 'Technical Support',
            'user_id' => $user2->id,
            'agent_id' => $agent2->id,
            'status' => 'active',
            'last_activity' => now()->subMinutes(5),
        ]);

        // Messages for conversation 2
        Message::create([
            'conversation_id' => $conversation2->id,
            'content' => 'Hi there! I\'m experiencing some technical issues.',
            'sender_type' => 'user',
            'sender_id' => $user2->id,
            'is_read' => true,
            'created_at' => now()->subMinutes(15),
        ]);

        Message::create([
            'conversation_id' => $conversation2->id,
            'content' => 'Hello John! I\'m here to help. Can you describe the technical issues you\'re facing?',
            'sender_type' => 'agent',
            'sender_id' => $agent2->id,
            'is_read' => true,
            'created_at' => now()->subMinutes(14),
        ]);

        Message::create([
            'conversation_id' => $conversation2->id,
            'content' => 'The website seems to be loading very slowly, and sometimes images don\'t load at all.',
            'sender_type' => 'user',
            'sender_id' => $user2->id,
            'is_read' => true,
            'created_at' => now()->subMinutes(13),
        ]);

        Message::create([
            'conversation_id' => $conversation2->id,
            'content' => 'I understand the frustration. Let me check our server status and help you troubleshoot. Are you experiencing this on all browsers?',
            'sender_type' => 'agent',
            'sender_id' => $agent2->id,
            'is_read' => false,
            'created_at' => now()->subMinutes(5),
        ]);

        // Create an unassigned conversation
        $conversation3 = Conversation::create([
            'title' => 'New Inquiry',
            'user_id' => $user1->id,
            'agent_id' => null,
            'status' => 'waiting',
            'last_activity' => now()->subMinutes(2),
        ]);

        Message::create([
            'conversation_id' => $conversation3->id,
            'content' => 'Hello! I have a question about your services.',
            'sender_type' => 'user',
            'sender_id' => $user1->id,
            'is_read' => false,
            'created_at' => now()->subMinutes(2),
        ]);

        $this->info('✅ Created conversation 1: Account Help Request');
        $this->info('✅ Created conversation 2: Technical Support');
        $this->info('✅ Created conversation 3: New Inquiry (unassigned)');
        $this->info('');
        $this->info('🎉 Sample data created successfully!');
        $this->info('');
        $this->info('📊 Summary:');
        $this->info('  • 3 conversations created');
        $this->info('  • 8 messages created');
        $this->info('  • Mix of read/unread messages');
        $this->info('  • One unassigned conversation for agents to claim');

        return 0;
    }
}
