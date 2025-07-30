<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Conversation;
use App\Services\ConversationAssignmentService;
use Illuminate\Console\Command;

class ProcessWaitingConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:process-waiting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process waiting conversations and assign them to available agents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Processing waiting conversations...');

        $assignmentService = new ConversationAssignmentService();
        
        // Get waiting conversations
        $waitingConversations = Conversation::where('status', 'waiting')
            ->whereNull('agent_id')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($waitingConversations->isEmpty()) {
            $this->info('✅ No waiting conversations found.');
            return 0;
        }

        $this->info("📋 Found {$waitingConversations->count()} waiting conversations");

        $assignedCount = 0;
        
        foreach ($waitingConversations as $conversation) {
            $agent = $assignmentService->autoAssignConversation($conversation);
            
            if ($agent) {
                $assignedCount++;
                $this->info("✅ Assigned conversation #{$conversation->id} to {$agent->name} ({$agent->email})");
            } else {
                $this->warn("⚠️  Could not assign conversation #{$conversation->id} - no available agents");
            }
        }

        $remainingWaiting = $waitingConversations->count() - $assignedCount;

        $this->info('');
        $this->info('📊 Summary:');
        $this->info("  • Total waiting: {$waitingConversations->count()}");
        $this->info("  • Successfully assigned: {$assignedCount}");
        $this->info("  • Still waiting: {$remainingWaiting}");
        
        // Show agent workload
        $this->info('');
        $this->info('👥 Current agent workload:');
        
        $onlineAgents = Agent::where('status', 'online')
            ->withCount(['conversations as active_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();

        if ($onlineAgents->isEmpty()) {
            $this->warn('  No agents currently online');
        } else {
            foreach ($onlineAgents as $agent) {
                $this->info("  • {$agent->name}: {$agent->active_count} active conversations");
            }
        }

        return 0;
    }
}
