<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationAssignmentService;
use Illuminate\Console\Command;

class TestBothSkillsAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:both-skills-assignment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that agents must have BOTH language AND domain skills';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 Testing BOTH Skills Requirement Assignment System');
        $this->newLine();

        // Get a test user
        $user = User::first();
        if (!$user) {
            $this->error('No users found in database');
            return 1;
        }

        $assignmentService = new ConversationAssignmentService();

        // Test cases that require BOTH skills
        $testCases = [
            ['SI', 'FINANCE', 'Support Agent (has SI+FINANCE)'],
            ['EN', 'HR', 'Support Agent or Kamala (both have EN+HR)'],
            ['TI', 'IT', 'Senior Agent (has TI+IT but offline)'],
            ['SI', 'IT', 'No one (no agent has SI+IT)'],
            ['TI', 'FINANCE', 'No one (no agent has TI+FINANCE)'],
            ['EN', 'NETWORK', 'No one (no agent has EN+NETWORK)'],
        ];

        foreach ($testCases as $index => $case) {
            [$language, $domain, $expected] = $case;
            
            $this->info("🎯 Test Case " . ($index + 1) . ": Language={$language}, Domain={$domain}");
            $this->info("   Expected: {$expected}");
            
            // Create test conversation
            $conversation = Conversation::create([
                'title' => "Test Both Skills {$language}+{$domain}",
                'user_id' => $user->id,
                'status' => 'waiting',
                'preferred_language' => $language,
                'preferred_domain' => $domain,
                'last_activity' => now()
            ]);

            // Try to assign
            $assignedAgent = $assignmentService->autoAssignConversation($conversation);
            
            if ($assignedAgent) {
                $this->info("   ✅ Assigned to: {$assignedAgent->name}");
                
                // Verify the agent actually has both skills
                $hasLanguage = $assignedAgent->hasLanguageSkill($language);
                $hasDomain = $assignedAgent->hasDomainSkill($domain);
                
                $this->info("   📊 Agent has language {$language}: " . ($hasLanguage ? 'YES' : 'NO'));
                $this->info("   📊 Agent has domain {$domain}: " . ($hasDomain ? 'YES' : 'NO'));
                $this->info("   📊 Both skills verified: " . ($hasLanguage && $hasDomain ? 'YES' : 'NO'));
                
                if (!($hasLanguage && $hasDomain)) {
                    $this->error("   ❌ ERROR: Agent doesn't have both required skills!");
                }
            } else {
                $this->info("   ⚠️ No agent assigned");
            }
            
            // Clean up
            $conversation->delete();
            $this->newLine();
        }

        $this->info('🎉 Both skills requirement testing completed!');
        return 0;
    }
}
