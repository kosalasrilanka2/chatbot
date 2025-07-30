<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Sample Users
        $users = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com', 
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Carol Davis',
                'email' => 'carol@example.com',
                'password' => Hash::make('password'),
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Create Sample Agents with Skills
        $agentData = [
            [
                'user' => [
                    'name' => 'Sarah Connor',
                    'email' => 'sarah@chatbot.com',
                    'password' => Hash::make('password'),
                ],
                'agent' => [
                    'name' => 'Sarah Connor',
                    'email' => 'sarah@chatbot.com',
                    'status' => 'online',
                    'last_seen' => now(),
                ],
                'skills' => [
                    ['skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English', 'proficiency_level' => 5],
                    ['skill_type' => 'language', 'skill_code' => 'SI', 'skill_name' => 'Sinhala', 'proficiency_level' => 4],
                    ['skill_type' => 'domain', 'skill_code' => 'IT', 'skill_name' => 'Information Technology', 'proficiency_level' => 5],
                    ['skill_type' => 'domain', 'skill_code' => 'NETWORK', 'skill_name' => 'Network Support', 'proficiency_level' => 4],
                ]
            ],
            [
                'user' => [
                    'name' => 'Mike Wilson',
                    'email' => 'mike@chatbot.com',
                    'password' => Hash::make('password'),
                ],
                'agent' => [
                    'name' => 'Mike Wilson',
                    'email' => 'mike@chatbot.com',
                    'status' => 'online',
                    'last_seen' => now(),
                ],
                'skills' => [
                    ['skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English', 'proficiency_level' => 5],
                    ['skill_type' => 'language', 'skill_code' => 'TI', 'skill_name' => 'Tamil', 'proficiency_level' => 5],
                    ['skill_type' => 'domain', 'skill_code' => 'FINANCE', 'skill_name' => 'Finance', 'proficiency_level' => 5],
                    ['skill_type' => 'domain', 'skill_code' => 'HR', 'skill_name' => 'Human Resources', 'proficiency_level' => 3],
                ]
            ],
            [
                'user' => [
                    'name' => 'Lisa Zhang',
                    'email' => 'lisa@chatbot.com',
                    'password' => Hash::make('password'),
                ],
                'agent' => [
                    'name' => 'Lisa Zhang',
                    'email' => 'lisa@chatbot.com',
                    'status' => 'busy',
                    'last_seen' => now(),
                ],
                'skills' => [
                    ['skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English', 'proficiency_level' => 5],
                    ['skill_type' => 'language', 'skill_code' => 'SI', 'skill_name' => 'Sinhala', 'proficiency_level' => 3],
                    ['skill_type' => 'domain', 'skill_code' => 'HR', 'skill_name' => 'Human Resources', 'proficiency_level' => 5],
                    ['skill_type' => 'domain', 'skill_code' => 'FINANCE', 'skill_name' => 'Finance', 'proficiency_level' => 4],
                ]
            ],
            [
                'user' => [
                    'name' => 'David Kumar',
                    'email' => 'david@chatbot.com',
                    'password' => Hash::make('password'),
                ],
                'agent' => [
                    'name' => 'David Kumar',
                    'email' => 'david@chatbot.com',
                    'status' => 'offline',
                    'last_seen' => now()->subMinutes(30),
                ],
                'skills' => [
                    ['skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English', 'proficiency_level' => 4],
                    ['skill_type' => 'language', 'skill_code' => 'TI', 'skill_name' => 'Tamil', 'proficiency_level' => 5],
                    ['skill_type' => 'language', 'skill_code' => 'SI', 'skill_name' => 'Sinhala', 'proficiency_level' => 4],
                    ['skill_type' => 'domain', 'skill_code' => 'IT', 'skill_name' => 'Information Technology', 'proficiency_level' => 3],
                ]
            ],
            [
                'user' => [
                    'name' => 'Emma Brown',
                    'email' => 'emma@chatbot.com',
                    'password' => Hash::make('password'),
                ],
                'agent' => [
                    'name' => 'Emma Brown',
                    'email' => 'emma@chatbot.com',
                    'status' => 'online',
                    'last_seen' => now(),
                ],
                'skills' => [
                    ['skill_type' => 'language', 'skill_code' => 'EN', 'skill_name' => 'English', 'proficiency_level' => 5],
                    ['skill_type' => 'domain', 'skill_code' => 'NETWORK', 'skill_name' => 'Network Support', 'proficiency_level' => 5],
                    ['skill_type' => 'domain', 'skill_code' => 'IT', 'skill_name' => 'Information Technology', 'proficiency_level' => 4],
                ]
            ]
        ];

        foreach ($agentData as $data) {
            // Create user account for agent
            $user = User::create($data['user']);
            
            // Create agent profile
            $agent = Agent::create($data['agent']);
            
            // Create agent skills
            foreach ($data['skills'] as $skillData) {
                $skillData['agent_id'] = $agent->id;
                AgentSkill::create($skillData);
            }
        }

        // Create sample conversations
        $this->createSampleConversations();
    }

    private function createSampleConversations()
    {
        $users = User::whereNotIn('email', ['sarah@chatbot.com', 'mike@chatbot.com', 'lisa@chatbot.com', 'david@chatbot.com', 'emma@chatbot.com'])->get();
        $agents = Agent::all();

        // Active conversation with messages
        $conv1 = Conversation::create([
            'title' => 'IT Support Request',
            'user_id' => $users[0]->id,
            'agent_id' => $agents[0]->id,
            'status' => 'active',
            'preferred_language' => 'EN',
            'preferred_domain' => 'IT',
            'last_activity' => now()->subMinutes(5),
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'content' => 'Hi, I need help with my computer. It won\'t start properly.',
            'sender_type' => 'user',
            'sender_id' => $users[0]->id,
            'created_at' => now()->subMinutes(30),
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'content' => 'Hello! I\'d be happy to help you with your computer issue. Can you tell me what happens when you try to start it?',
            'sender_type' => 'agent',
            'sender_id' => $agents[0]->id,
            'created_at' => now()->subMinutes(25),
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'content' => 'It shows a blue screen and then restarts itself.',
            'sender_type' => 'user',
            'sender_id' => $users[0]->id,
            'created_at' => now()->subMinutes(20),
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'content' => 'That sounds like a Blue Screen of Death (BSOD). Let\'s try booting in safe mode first. Can you restart your computer and press F8 repeatedly during startup?',
            'sender_type' => 'agent',
            'sender_id' => $agents[0]->id,
            'created_at' => now()->subMinutes(15),
        ]);

        // Finance conversation
        $conv2 = Conversation::create([
            'title' => 'Budget Planning Question',
            'user_id' => $users[1]->id,
            'agent_id' => $agents[1]->id,
            'status' => 'active',
            'preferred_language' => 'EN',
            'preferred_domain' => 'FINANCE',
            'last_activity' => now()->subHours(1),
        ]);

        Message::create([
            'conversation_id' => $conv2->id,
            'content' => 'I need help understanding the new budget allocation process.',
            'sender_type' => 'user',
            'sender_id' => $users[1]->id,
            'created_at' => now()->subHours(2),
        ]);

        Message::create([
            'conversation_id' => $conv2->id,
            'content' => 'I\'ll be glad to help you with the budget allocation process. What specific aspect would you like me to explain?',
            'sender_type' => 'agent',
            'sender_id' => $agents[1]->id,
            'created_at' => now()->subHours(1),
        ]);

        // Waiting conversation (no agent assigned)
        $conv3 = Conversation::create([
            'title' => 'HR Policy Question',
            'user_id' => $users[2]->id,
            'agent_id' => null,
            'status' => 'waiting',
            'preferred_language' => 'SI',
            'preferred_domain' => 'HR',
            'last_activity' => now()->subMinutes(10),
        ]);

        Message::create([
            'conversation_id' => $conv3->id,
            'content' => 'සුභ දවසක්! මට නිවාඩු ප්‍රතිපත්තිය ගැන කිහිපයක් විමසීමට අවශ්‍යයි.',
            'sender_type' => 'user',
            'sender_id' => $users[2]->id,
            'created_at' => now()->subMinutes(10),
        ]);

        // Another waiting conversation
        $conv4 = Conversation::create([
            'title' => 'Network Connectivity Issue',
            'user_id' => $users[0]->id,
            'agent_id' => null,
            'status' => 'waiting',
            'preferred_language' => 'EN',
            'preferred_domain' => 'NETWORK',
            'last_activity' => now()->subMinutes(2),
        ]);

        Message::create([
            'conversation_id' => $conv4->id,
            'content' => 'My office internet connection is very slow today. Can someone help?',
            'sender_type' => 'user',
            'sender_id' => $users[0]->id,
            'created_at' => now()->subMinutes(2),
        ]);

        // Closed conversation
        $conv5 = Conversation::create([
            'title' => 'Password Reset - Resolved',
            'user_id' => $users[1]->id,
            'agent_id' => $agents[4]->id,
            'status' => 'closed',
            'preferred_language' => 'EN',
            'preferred_domain' => 'IT',
            'last_activity' => now()->subDays(1),
        ]);

        Message::create([
            'conversation_id' => $conv5->id,
            'content' => 'I forgot my password and need to reset it.',
            'sender_type' => 'user',
            'sender_id' => $users[1]->id,
            'created_at' => now()->subDays(1)->subMinutes(30),
        ]);

        Message::create([
            'conversation_id' => $conv5->id,
            'content' => 'I can help you reset your password. Please check your email for a reset link.',
            'sender_type' => 'agent',
            'sender_id' => $agents[4]->id,
            'created_at' => now()->subDays(1)->subMinutes(25),
        ]);

        Message::create([
            'conversation_id' => $conv5->id,
            'content' => 'Perfect! I received the email and was able to reset my password. Thank you!',
            'sender_type' => 'user',
            'sender_id' => $users[1]->id,
            'created_at' => now()->subDays(1)->subMinutes(20),
        ]);

        Message::create([
            'conversation_id' => $conv5->id,
            'content' => 'Great! Glad I could help. Is there anything else you need assistance with?',
            'sender_type' => 'agent',
            'sender_id' => $agents[4]->id,
            'created_at' => now()->subDays(1)->subMinutes(15),
        ]);

        Message::create([
            'conversation_id' => $conv5->id,
            'content' => 'No, that\'s all. Thanks again!',
            'sender_type' => 'user',
            'sender_id' => $users[1]->id,
            'created_at' => now()->subDays(1)->subMinutes(10),
        ]);
    }
}
