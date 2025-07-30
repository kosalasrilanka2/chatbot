<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugSkillQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:skill-query {language} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug skill-based query for agents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $language = $this->argument('language');
        $domain = $this->argument('domain');
        
        $this->info("🔍 Debugging skill query for Language={$language}, Domain={$domain}");
        $this->newLine();

        // Test the query logic step by step
        $this->info("Step 1: Basic online agents");
        $onlineAgents = Agent::where('status', 'online')->get();
        foreach ($onlineAgents as $agent) {
            $this->info("  • {$agent->name} (online)");
        }
        $this->newLine();

        $this->info("Step 2: Agents with language skill {$language}");
        $langAgents = Agent::where('status', 'online')
            ->whereHas('skills', function ($query) use ($language) {
                $query->where('skill_type', 'language')
                      ->where('skill_code', $language);
            })->get();
        foreach ($langAgents as $agent) {
            $this->info("  • {$agent->name} (has language {$language})");
        }
        $this->newLine();

        $this->info("Step 3: Agents with domain skill {$domain}");
        $domainAgents = Agent::where('status', 'online')
            ->whereHas('skills', function ($query) use ($domain) {
                $query->where('skill_type', 'domain')
                      ->where('skill_code', $domain);
            })->get();
        foreach ($domainAgents as $agent) {
            $this->info("  • {$agent->name} (has domain {$domain})");
        }
        $this->newLine();

        $this->info("Step 4: Agents with BOTH skills AND within capacity");
        $bothSkillsAgents = Agent::where('status', 'online')
            ->withCount([
                'conversations as active_conversations_count' => function ($query) {
                    $query->whereIn('status', ['active', 'waiting']);
                }
            ])
            ->having('active_conversations_count', '<', 5) // MAX_CONVERSATIONS_PER_AGENT = 5
            ->whereHas('skills', function ($langQuery) use ($language) {
                $langQuery->where('skill_type', 'language')
                        ->where('skill_code', $language);
            })
            ->whereHas('skills', function ($domainQuery) use ($domain) {
                $domainQuery->where('skill_type', 'domain')
                          ->where('skill_code', $domain);
            })->get();
        
        if ($bothSkillsAgents->count() > 0) {
            foreach ($bothSkillsAgents as $agent) {
                $this->info("  ✅ {$agent->name} (has both {$language} and {$domain}, conversations: {$agent->active_conversations_count})");
            }
        } else {
            $this->info("  ⚠️ No agents found with both skills within capacity limits");
        }
        $this->newLine();

        $this->info("Step 5: Raw SQL query for debugging");
        $query = Agent::where('status', 'online')
            ->whereHas('skills', function ($langQuery) use ($language) {
                $langQuery->where('skill_type', 'language')
                        ->where('skill_code', $language);
            })
            ->whereHas('skills', function ($domainQuery) use ($domain) {
                $domainQuery->where('skill_type', 'domain')
                          ->where('skill_code', $domain);
            });
        
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        $this->info("SQL: " . $sql);
        $this->info("Bindings: " . json_encode($bindings));

        return 0;
    }
}
