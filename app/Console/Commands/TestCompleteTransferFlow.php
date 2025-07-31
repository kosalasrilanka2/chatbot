<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Agent;
use App\Models\User;
use App\Models\Message;
use App\Services\ConversationAssignmentService;

class TestCompleteTransferFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:complete-transfer-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete conversation transfer flow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Complete Transfer Flow...');

        // Create test user
        $user = User::firstOrCreate([
            'email' => 'transfertest@example.com'
        ], [
            'name' => 'Transfer Test User',
            'password' => bcrypt('password')
        ]);

        // Get agents
        $agents = Agent::where('status', '!=', 'offline')->limit(3)->get();
        if ($agents->count() < 2) {
            $this->error('Need at least 2 online agents for testing');
            return;
        }

        $agent1 = $agents[0];
        $agent2 = $agents[1];

        $this->info("👥 Using agents:");
        $this->info("   - Agent 1: {$agent1->name} (Status: {$agent1->status})");
        $this->info("   - Agent 2: {$agent2->name} (Status: {$agent2->status})");

        // Create conversation assigned to first agent
        $conversation = Conversation::create([
            'title' => 'Transfer Flow Test',
            'user_id' => $user->id,
            'agent_id' => $agent1->id,
            'status' => 'active',
            'last_activity' => now(),
            'unread_count' => 0,
            'is_transferred' => false,
            'transfer_count' => 0
        ]);

        $this->info("💬 Created conversation {$conversation->id} assigned to {$agent1->name}");

        // Add some user messages
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Hello, I need help with my account',
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'is_read' => true
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'It seems to be locked',
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'is_read' => true
        ]);

        $this->info("📝 Added user messages");

        // Step 1: Simulate agent 1 going offline (triggers transfer)
        $this->info("\n🔄 Step 1: Simulating {$agent1->name} going offline...");
        
        // Actually set the agent offline first
        $agent1->update(['status' => 'offline']);
        $this->info("🔌 Set {$agent1->name} status to offline");
        
        $assignmentService = new ConversationAssignmentService();
        $redistributedCount = $assignmentService->redistributeConversationsFromOfflineAgent($agent1);
        
        $this->info("📤 Redistributed {$redistributedCount} conversations");

        // Check conversation status after offline transfer
        $conversation->refresh();
        $this->info("📊 Conversation status after agent offline:");
        $this->info("   - Agent ID: " . ($conversation->agent_id ?? 'null'));
        $this->info("   - Status: {$conversation->status}");
        $this->info("   - Is Transferred: " . ($conversation->is_transferred ? 'Yes' : 'No'));
        $this->info("   - Transfer Count: {$conversation->transfer_count}");

        // Step 2: Simulate agent 2 picking up the conversation
        if ($conversation->agent_id === null && $conversation->status === 'waiting') {
            $this->info("\n🤝 Step 2: Simulating {$agent2->name} picking up the transferred conversation...");
            
            // This simulates what happens in AgentController::assignConversation
            $wasTransferred = $conversation->is_transferred;
            $oldAgent = $agent1; // Keep reference to original agent
            
            $conversation->update([
                'agent_id' => $agent2->id,
                'status' => 'active'
            ]);

            if ($wasTransferred) {
                $assignmentMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'content' => "Hi! I'm {$agent2->name} and I'll be continuing to assist you. I have your full conversation history and I'm here to help!",
                    'sender_type' => 'system',
                    'sender_id' => null,
                ]);
                
                $this->info("💬 Created assignment message from {$agent2->name}");
            }

            $conversation->refresh();
            $this->info("📊 Final conversation status:");
            $this->info("   - Agent ID: {$conversation->agent_id}");
            $this->info("   - Status: {$conversation->status}");
            $this->info("   - Is Transferred: " . ($conversation->is_transferred ? 'Yes' : 'No'));
            $this->info("   - Transfer Count: {$conversation->transfer_count}");

            $this->info("✅ Transfer completed successfully!");
        } else if ($conversation->agent_id !== null) {
            $newAgent = Agent::find($conversation->agent_id);
            $this->info("✅ Conversation was automatically assigned to {$newAgent->name}");
        } else {
            $this->info("⏳ Conversation is waiting for manual pickup by any agent");
        }

        // Show message count
        $messageCount = $conversation->messages()->count();
        $this->info("📧 Total messages in conversation: {$messageCount}");

        // Clean up
        $conversation->messages()->delete();
        $conversation->delete();
        $user->delete();

        $this->info("\n🧹 Test completed and cleaned up");
    }
}
