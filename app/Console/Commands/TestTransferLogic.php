<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Agent;
use App\Services\ConversationAssignmentService;

class TestTransferLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:transfer-logic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test conversation transfer logic and marking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing transfer logic...');

        // Get the latest conversation
        $conversation = Conversation::latest()->first();
        if (!$conversation) {
            $this->error('No conversations found. Please create a conversation first.');
            return;
        }

        $this->info("Testing with conversation {$conversation->id}");

        // Get first two agents
        $agents = Agent::limit(2)->get();
        if ($agents->count() < 2) {
            $this->error('Need at least 2 agents for transfer testing.');
            return;
        }

        $agent1 = $agents[0];
        $agent2 = $agents[1];

        $this->info("Agent 1: {$agent1->name} (ID: {$agent1->id})");
        $this->info("Agent 2: {$agent2->name} (ID: {$agent2->id})");

        // Assign conversation to first agent
        $conversation->update([
            'agent_id' => $agent1->id,
            'status' => 'active'
        ]);
        $this->info("Assigned conversation to {$agent1->name}");

        // Simulate agent going offline and triggering transfer
        $this->info("Simulating {$agent1->name} going offline...");
        
        $assignmentService = new ConversationAssignmentService();
        $redistributedCount = $assignmentService->redistributeConversationsFromOfflineAgent($agent1);

        $this->info("Redistributed {$redistributedCount} conversations");

        // Check the conversation status
        $conversation->refresh();
        $this->info("Conversation status after transfer:");
        $this->info("- Agent ID: " . ($conversation->agent_id ?? 'null'));
        $this->info("- Status: {$conversation->status}");
        $this->info("- Is Transferred: " . ($conversation->is_transferred ? 'Yes' : 'No'));
        $this->info("- Transfer Count: {$conversation->transfer_count}");
        $this->info("- Last Transferred: " . ($conversation->last_transferred_at ? $conversation->last_transferred_at->format('Y-m-d H:i:s') : 'null'));

        $this->info('✅ Transfer logic test completed');
    }
}
