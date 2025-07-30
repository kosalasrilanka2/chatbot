<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user or get existing one
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User']
        );

        // Create test agent or get existing one
        $agent = Agent::firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Support Agent',
                'status' => 'online',
            ]
        );

        // Create a conversation if it doesn't exist
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $user->id,
                'agent_id' => $agent->id,
            ],
            [
                'title' => 'Welcome Chat',
                'status' => 'active',
                'last_activity' => now(),
            ]
        );

        // Only create messages if conversation is new
        if ($conversation->messages()->count() === 0) {
            Message::create([
                'conversation_id' => $conversation->id,
                'content' => 'Hello! Welcome to our chat support. How can I help you today?',
                'sender_type' => 'agent',
                'sender_id' => $agent->id,
                'is_read' => true,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'content' => 'Hi there! I need help with my account.',
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'is_read' => true,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'content' => 'I\'d be happy to help you with your account. What specific issue are you experiencing?',
                'sender_type' => 'agent',
                'sender_id' => $agent->id,
                'is_read' => false,
            ]);
        }
    }
}
