<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agent;
use App\Services\ConversationAssignmentService;
use App\Events\AgentStatusUpdated;
use App\Events\AgentForceLogout;
use Carbon\Carbon;

class CheckAgentPresence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:check-presence {--timeout=45 : Timeout in seconds for inactive agents (3 missed heartbeats)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check agent presence and set inactive agents offline after 3 missed heartbeats';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutSeconds = $this->option('timeout');
        $cutoffTime = Carbon::now()->subSeconds($timeoutSeconds);
        
        $this->info("Checking for agents inactive for more than {$timeoutSeconds} seconds...");
        
        // Find agents who are marked as online/busy but haven't been seen recently
        $inactiveAgents = Agent::where('status', '!=', 'offline')
            ->where('last_seen', '<', $cutoffTime)
            ->get();
        
        if ($inactiveAgents->isEmpty()) {
            $this->info('✅ All agents are active or already offline.');
            return 0;
        }
        
        $this->info("Found {$inactiveAgents->count()} inactive agents:");
        
        $assignmentService = new ConversationAssignmentService();
        $totalRedistributed = 0;
        
        foreach ($inactiveAgents as $agent) {
            $inactiveMinutes = $agent->last_seen->diffInMinutes(Carbon::now());
            $this->line("  - {$agent->name} ({$agent->email}) - Last seen: {$inactiveMinutes} minutes ago");
            
            // Set agent offline
            $agent->update([
                'status' => 'offline',
                'last_seen' => Carbon::now()
            ]);
            
            // Broadcast status change
            broadcast(new AgentStatusUpdated($agent));
            
            // If the agent was recently seen (within last hour), send force logout
            if ($inactiveMinutes < 60) {
                try {
                    broadcast(new AgentForceLogout($agent));
                    $this->line("    → Sent force logout signal");
                } catch (\Exception $e) {
                    $this->error("    → Failed to send logout signal: " . $e->getMessage());
                }
            }
            
            // Redistribute their conversations to other available agents
            $redistributed = $assignmentService->redistributeConversationsFromOfflineAgent($agent);
            $totalRedistributed += $redistributed;
            
            if ($redistributed > 0) {
                $this->line("    → Redistributed {$redistributed} conversations");
            }
        }
        
        $this->info("✅ Set {$inactiveAgents->count()} agents offline");
        
        if ($totalRedistributed > 0) {
            $this->info("🔄 Redistributed {$totalRedistributed} conversations to available agents");
        }
        
        return 0;
    }
}
