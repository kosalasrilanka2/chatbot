<?php

namespace App\Services;

use App\Events\NewMessageEvent;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ConversationAssignmentService
{
    /**
     * Automatically assign a conversation to an available agent
     */
    public function autoAssignConversation(Conversation $conversation): ?Agent
    {
        // Don't reassign if already assigned
        if ($conversation->agent_id) {
            return null;
        }

        $agent = $this->findBestAvailableAgent();
        
        if ($agent) {
            $this->assignConversationToAgent($conversation, $agent);
            $this->notifyAgentOfNewAssignment($conversation, $agent);
            
            Log::info("Auto-assigned conversation {$conversation->id} to agent {$agent->id} ({$agent->name})");
            
            return $agent;
        }

        Log::info("No available agents for conversation {$conversation->id}");
        return null;
    }

    /**
     * Find the best available agent using intelligent distribution
     */
    private function findBestAvailableAgent(): ?Agent
    {
        // Get online agents ordered by workload (least busy first)
        $availableAgents = Agent::where('status', 'online')
            ->withCount(['conversations as active_conversations_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('active_conversations_count', 'asc')
            ->orderBy('last_seen', 'asc') // Secondary sort: least recently active
            ->get();

        if ($availableAgents->isEmpty()) {
            return null;
        }

        // If multiple agents have the same conversation count, pick the one who was active longest ago
        $leastBusyCount = $availableAgents->first()->active_conversations_count;
        
        $bestCandidates = $availableAgents->filter(function ($agent) use ($leastBusyCount) {
            return $agent->active_conversations_count === $leastBusyCount;
        });

        // Return the agent who was least recently active among the least busy ones
        return $bestCandidates->sortBy('last_seen')->first();
    }

    /**
     * Assign conversation to specific agent
     */
    private function assignConversationToAgent(Conversation $conversation, Agent $agent): void
    {
        $conversation->update([
            'agent_id' => $agent->id,
            'status' => 'active',
            'last_activity' => now()
        ]);
    }

    /**
     * Notify agent of new assignment with system message
     */
    private function notifyAgentOfNewAssignment(Conversation $conversation, Agent $agent): void
    {
        $systemMessage = Message::create([
            'conversation_id' => $conversation->id,
            'content' => "This conversation has been automatically assigned to {$agent->name}. How can I help you today?",
            'sender_type' => 'system',
            'sender_id' => null,
        ]);

        // Broadcast the assignment notification
        broadcast(new NewMessageEvent($systemMessage));
    }

    /**
     * Process waiting conversations when an agent comes online
     */
    public function processWaitingConversationsForAgent(Agent $agent): int
    {
        if ($agent->status !== 'online') {
            return 0;
        }

        // Get waiting conversations (oldest first)
        $waitingConversations = Conversation::where('status', 'waiting')
            ->whereNull('agent_id')
            ->orderBy('created_at', 'asc')
            ->limit(3) // Assign max 3 conversations when coming online
            ->get();

        $assignedCount = 0;

        foreach ($waitingConversations as $conversation) {
            // Check if agent is still available (not overloaded)
            $currentLoad = Conversation::where('agent_id', $agent->id)
                ->where('status', 'active')
                ->count();

            if ($currentLoad >= 5) { // Max 5 active conversations per agent
                break;
            }

            $this->assignConversationToAgent($conversation, $agent);
            $this->notifyAgentOfNewAssignment($conversation, $agent);
            $assignedCount++;

            Log::info("Assigned waiting conversation {$conversation->id} to newly online agent {$agent->id}");
        }

        return $assignedCount;
    }

    /**
     * Get assignment statistics for admin dashboard
     */
    public function getAssignmentStats(): array
    {
        $stats = [
            'online_agents' => Agent::where('status', 'online')->count(),
            'waiting_conversations' => Conversation::where('status', 'waiting')->whereNull('agent_id')->count(),
            'active_conversations' => Conversation::where('status', 'active')->count(),
            'agent_workload' => []
        ];

        // Get workload per agent
        $agentWorkload = Agent::where('status', 'online')
            ->withCount(['conversations as active_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get(['id', 'name', 'email'])
            ->map(function ($agent) {
                return [
                    'agent_name' => $agent->name,
                    'agent_email' => $agent->email,
                    'active_conversations' => $agent->active_count,
                    'status' => 'online'
                ];
            });

        $stats['agent_workload'] = $agentWorkload;

        return $stats;
    }

    /**
     * Redistribute conversations if an agent goes offline unexpectedly
     */
    public function redistributeConversationsFromOfflineAgent(Agent $agent): int
    {
        $activeConversations = Conversation::where('agent_id', $agent->id)
            ->where('status', 'active')
            ->get();

        $redistributedCount = 0;

        foreach ($activeConversations as $conversation) {
            // Reset conversation to waiting status
            $conversation->update([
                'agent_id' => null,
                'status' => 'waiting',
                'last_activity' => now()
            ]);

            // Try to reassign immediately
            $newAgent = $this->autoAssignConversation($conversation);
            
            if ($newAgent) {
                $redistributedCount++;
            } else {
                // Create system message explaining the situation
                Message::create([
                    'conversation_id' => $conversation->id,
                    'content' => 'Your previous agent is no longer available. Please wait while we connect you to another agent.',
                    'sender_type' => 'system',
                    'sender_id' => null,
                ]);
            }
        }

        Log::info("Redistributed {$redistributedCount} conversations from offline agent {$agent->id}");

        return $redistributedCount;
    }
}
