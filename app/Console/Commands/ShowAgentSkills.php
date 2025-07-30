<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

class ShowAgentSkills extends Command
{
    protected $signature = 'show:agent-skills';
    protected $description = 'Show agent skills for testing';

    public function handle()
    {
        $this->info('🎯 Agent Skills Display Test');
        
        $agents = Agent::with('skills')->get();
        
        foreach ($agents as $agent) {
            $this->info("Agent: {$agent->name} ({$agent->email})");
            
            $languageSkills = $agent->skills->where('skill_type', 'language')->pluck('skill_code');
            $domainSkills = $agent->skills->where('skill_type', 'domain')->pluck('skill_code');
            
            $this->info("  Languages: " . $languageSkills->join(', '));
            $this->info("  Domains: " . $domainSkills->join(', '));
            $this->newLine();
        }
        
        return 0;
    }
}
