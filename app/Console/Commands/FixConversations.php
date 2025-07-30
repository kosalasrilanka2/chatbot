<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use Illuminate\Console\Command;

class FixConversations extends Command
{
    protected $signature = 'chat:fix-conversations';
    protected $description = 'Fix conversation agent assignments';

    public function handle()
    {
        $this->info('🔧 Fixing Conversation Assignments...');

        $agent = Agent::where('email', 'agent@example.com')->first();
        if (!$agent) {
            $this->error('❌ Agent not found');
            return 1;
        }

        // Show current conversations
        $conversations = Conversation::with(['user'])->get();
        $this->info("📋 Current Conversations:");
        
        foreach ($conversations as $conv) {
            $this->info("   ID: {$conv->id}, User: {$conv->user_id}, Agent: " . ($conv->agent_id ?? 'NULL') . ", Status: {$conv->status}");
        }

        // Assign agent to unassigned conversations
        $updated = Conversation::whereNull('agent_id')->update([
            'agent_id' => $agent->id,
            'status' => 'active'
        ]);

        $this->info("✅ Updated {$updated} conversations with agent ID {$agent->id}");

        // Show updated conversations
        $conversations = Conversation::with(['user'])->get();
        $this->info("📋 Updated Conversations:");
        
        foreach ($conversations as $conv) {
            $this->info("   ID: {$conv->id}, User: {$conv->user_id}, Agent: " . ($conv->agent_id ?? 'NULL') . ", Status: {$conv->status}");
        }

        return 0;
    }
}
