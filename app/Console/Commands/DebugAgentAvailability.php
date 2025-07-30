<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

class DebugAgentAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:agent-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug agent availability criteria';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Debugging Agent Availability');
        $this->newLine();

        $agents = Agent::with('skills')
            ->withCount([
                'conversations as active_conversations_count' => function ($query) {
                    $query->whereIn('status', ['active', 'waiting']);
                }
            ])
            ->get();

        foreach ($agents as $agent) {
            $this->info("👤 Agent: {$agent->name}");
            $this->info("   Status: {$agent->status}");
            $this->info("   Last Seen: " . ($agent->last_seen ? $agent->last_seen->diffForHumans() : 'Never'));
            $this->info("   Last Seen Time: " . ($agent->last_seen ? $agent->last_seen : 'NULL'));
            $this->info("   Active Conversations: {$agent->active_conversations_count}");
            
            // Check availability criteria (updated to match new logic)
            $isOnline = $agent->status === 'online';
            $withinCapacity = $agent->active_conversations_count < 5; // MAX_CONVERSATIONS_PER_AGENT = 5
            
            $this->info("   ✅ Online: " . ($isOnline ? 'Yes' : 'No'));
            $this->info("   ✅ Within Capacity: " . ($withinCapacity ? 'Yes' : 'No'));
            $this->info("   🎯 Available: " . ($isOnline && $withinCapacity ? 'YES' : 'NO'));
            
            // Show skills
            $skills = $agent->skills->groupBy('skill_type');
            $languages = $skills->get('language', collect())->pluck('skill_code')->join(', ');
            $domains = $skills->get('domain', collect())->pluck('skill_code')->join(', ');
            $this->info("   🛠️ Languages: [{$languages}]");
            $this->info("   🛠️ Domains: [{$domains}]");
            
            $this->newLine();
        }

        return 0;
    }
}
