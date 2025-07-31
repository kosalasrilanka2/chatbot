<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agent;

class CheckAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:agents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check agent status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Agent Status:');
        $agents = Agent::all(['id', 'name', 'status']);
        
        foreach ($agents as $agent) {
            $this->info("{$agent->id}: {$agent->name} - {$agent->status}");
        }
    }
}
