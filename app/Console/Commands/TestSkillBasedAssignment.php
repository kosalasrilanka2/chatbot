<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationAssignmentService;
use Illuminate\Console\Command;

class TestSkillBasedAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:skill-based-assignment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the skill-based agent assignment system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Skill-Based Agent Assignment System');
        $this->newLine();

        // Show available agents and their skills
        $this->info('📋 Available Agents and Skills:');
        $agents = Agent::with('skills')->get();
        
        foreach ($agents as $agent) {
            $skills = $agent->skills->groupBy('skill_type');
            $languages = $skills->get('language', collect())->pluck('skill_code')->join(', ');
            $domains = $skills->get('domain', collect())->pluck('skill_code')->join(', ');
            
            $this->info("   • {$agent->name} ({$agent->status}) - Languages: [{$languages}] Domains: [{$domains}]");
        }
        $this->newLine();

        // Test different skill combinations
        $testCases = [
            ['language' => 'SI', 'domain' => 'FINANCE'],
            ['language' => 'EN', 'domain' => 'IT'],
            ['language' => 'TI', 'domain' => 'HR'],
            ['language' => 'EN', 'domain' => null],
            ['language' => null, 'domain' => 'NETWORK'],
        ];

        $user = User::first();
        if (!$user) {
            $this->error('❌ No user found for testing');
            return 1;
        }

        foreach ($testCases as $index => $test) {
            $this->info("🎯 Test Case " . ($index + 1) . ": Language={$test['language']}, Domain={$test['domain']}");
            
            // Create test conversation
            $conversation = Conversation::create([
                'title' => "Test Skill Assignment " . ($index + 1),
                'user_id' => $user->id,
                'status' => 'waiting',
                'preferred_language' => $test['language'],
                'preferred_domain' => $test['domain'],
                'last_activity' => now()
            ]);

            // Try assignment
            $assignmentService = new ConversationAssignmentService();
            $assignedAgent = $assignmentService->autoAssignConversation($conversation);

            if ($assignedAgent) {
                $this->info("   ✅ Assigned to: {$assignedAgent->name}");
                
                // Show agent's matching skills
                $matchingSkills = $assignedAgent->skills->filter(function($skill) use ($test) {
                    return ($skill->skill_type === 'language' && $skill->skill_code === $test['language']) ||
                           ($skill->skill_type === 'domain' && $skill->skill_code === $test['domain']);
                });
                
                if ($matchingSkills->isNotEmpty()) {
                    $this->info("   📊 Matching skills: " . $matchingSkills->map(function($skill) {
                        return "{$skill->skill_type}:{$skill->skill_code}";
                    })->join(', '));
                }
            } else {
                $this->warn("   ⚠️ No agent assigned (no available skilled agents)");
            }
            
            $this->newLine();
        }

        $this->info('🎉 Skill-based assignment testing completed!');
        return 0;
    }
}
