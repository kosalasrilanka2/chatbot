<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentSkill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, create some sample agents if they don't exist
        $agents = [
            [
                'name' => 'Kamala Perera',
                'email' => 'kamala@chatbot.com',
                'status' => 'online',
                'last_seen' => now(),
                'skills' => [
                    // Languages
                    ['type' => 'language', 'code' => 'SI', 'name' => 'Sinhala', 'level' => 5],
                    ['type' => 'language', 'code' => 'EN', 'name' => 'English', 'level' => 4],
                    // Domains
                    ['type' => 'domain', 'code' => 'FINANCE', 'name' => 'Finance', 'level' => 5],
                    ['type' => 'domain', 'code' => 'HR', 'name' => 'Human Resources', 'level' => 3],
                ]
            ],
            [
                'name' => 'Suresh Kumar',
                'email' => 'suresh@chatbot.com',
                'status' => 'online',
                'last_seen' => now(),
                'skills' => [
                    // Languages
                    ['type' => 'language', 'code' => 'TI', 'name' => 'Tamil', 'level' => 5],
                    ['type' => 'language', 'code' => 'EN', 'name' => 'English', 'level' => 4],
                    // Domains
                    ['type' => 'domain', 'code' => 'IT', 'name' => 'Information Technology', 'level' => 5],
                    ['type' => 'domain', 'code' => 'NETWORK', 'name' => 'Network Support', 'level' => 4],
                ]
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@chatbot.com',
                'status' => 'online',
                'last_seen' => now(),
                'skills' => [
                    // Languages
                    ['type' => 'language', 'code' => 'EN', 'name' => 'English', 'level' => 5],
                    ['type' => 'language', 'code' => 'SI', 'name' => 'Sinhala', 'level' => 3],
                    // Domains
                    ['type' => 'domain', 'code' => 'HR', 'name' => 'Human Resources', 'level' => 5],
                    ['type' => 'domain', 'code' => 'FINANCE', 'name' => 'Finance', 'level' => 3],
                ]
            ],
            [
                'name' => 'Priya Wickramasinghe',
                'email' => 'priya@chatbot.com',
                'status' => 'online',
                'last_seen' => now(),
                'skills' => [
                    // Languages
                    ['type' => 'language', 'code' => 'SI', 'name' => 'Sinhala', 'level' => 5],
                    ['type' => 'language', 'code' => 'TI', 'name' => 'Tamil', 'level' => 4],
                    ['type' => 'language', 'code' => 'EN', 'name' => 'English', 'level' => 4],
                    // Domains
                    ['type' => 'domain', 'code' => 'IT', 'name' => 'Information Technology', 'level' => 4],
                    ['type' => 'domain', 'code' => 'NETWORK', 'name' => 'Network Support', 'level' => 5],
                ]
            ],
            [
                'name' => 'Rajesh Fernando',
                'email' => 'rajesh@chatbot.com',
                'status' => 'online',
                'last_seen' => now(),
                'skills' => [
                    // Languages
                    ['type' => 'language', 'code' => 'EN', 'name' => 'English', 'level' => 5],
                    ['type' => 'language', 'code' => 'SI', 'name' => 'Sinhala', 'level' => 3],
                    // Domains
                    ['type' => 'domain', 'code' => 'FINANCE', 'name' => 'Finance', 'level' => 5],
                    ['type' => 'domain', 'code' => 'HR', 'name' => 'Human Resources', 'level' => 2],
                ]
            ]
        ];

        foreach ($agents as $agentData) {
            // Create or find agent
            $agent = Agent::firstOrCreate(
                ['email' => $agentData['email']],
                [
                    'name' => $agentData['name'],
                    'status' => $agentData['status'],
                    'last_seen' => $agentData['last_seen']
                ]
            );

            // Add skills
            foreach ($agentData['skills'] as $skillData) {
                AgentSkill::firstOrCreate(
                    [
                        'agent_id' => $agent->id,
                        'skill_type' => $skillData['type'],
                        'skill_code' => $skillData['code']
                    ],
                    [
                        'skill_name' => $skillData['name'],
                        'proficiency_level' => $skillData['level']
                    ]
                );
            }
        }

        $this->command->info('Sample agents with skills created successfully!');
        $this->command->info('Agents created:');
        foreach ($agents as $agentData) {
            $this->command->info("- {$agentData['name']} ({$agentData['email']})");
            foreach ($agentData['skills'] as $skill) {
                $this->command->info("  * {$skill['name']} (Level {$skill['level']})");
            }
        }
    }
}
