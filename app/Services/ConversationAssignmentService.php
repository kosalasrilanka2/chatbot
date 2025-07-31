<?php

namespace App\Services;

use App\Events\NewMessageEvent;
use App\Events\AgentTransferNotification;
use App\Events\AgentHandoffNotification;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ConversationAssignmentService
{
    // Configuration constants for real-world scenarios
    const MAX_CONVERSATIONS_PER_AGENT = 5; // Typical call center limit
    const AGENT_OFFLINE_THRESHOLD_MINUTES = 5; // Consider offline after 5 minutes
    const HIGH_PRIORITY_QUEUE_LIMIT = 3; // Max high priority conversations per agent
    
    /**
     * Automatically assign a conversation to an available agent with skill-based routing
     */
    public function autoAssignConversation(Conversation $conversation, $priority = 'normal'): ?Agent
    {
        // Don't reassign if already assigned
        if ($conversation->agent_id) {
            return null;
        }

        $agent = $this->findBestAvailableAgentWithSkills($conversation, $priority);
        
        if (!$agent) {
            // Only fallback to basic assignment if no specific skills are required
            // If both language AND domain are specified, we require strict skill matching
            if (!$conversation->preferred_language && !$conversation->preferred_domain) {
                $agent = $this->findBestAvailableAgent($priority);
            }
        }
        
        if ($agent) {
            $this->assignConversationToAgent($conversation, $agent);
            $this->notifyAgentOfNewAssignment($conversation, $agent);
            
            Log::info("Auto-assigned conversation {$conversation->id} to agent {$agent->id} ({$agent->name}) with priority: {$priority}, language: {$conversation->preferred_language}, domain: {$conversation->preferred_domain}");
            
            return $agent;
        }

        Log::info("No available agents for conversation {$conversation->id} with priority: {$priority}, language: {$conversation->preferred_language}, domain: {$conversation->preferred_domain}");
        
        // Add to waiting queue with priority
        $this->addToWaitingQueue($conversation, $priority);
        
        return null;
    }

    /**
     * Enhanced agent selection with skills, capacity and priority management
     */
    private function findBestAvailableAgentWithSkills(Conversation $conversation, $priority = 'normal'): ?Agent
    {
        // Get basic availability criteria - only check online status, not last_seen
        $baseQuery = Agent::where('status', 'online')
            ->withCount([
                'conversations as active_conversations_count' => function ($query) {
                    $query->whereIn('status', ['active', 'waiting']);
                },
                'conversations as high_priority_count' => function ($query) {
                    $query->whereIn('status', ['active', 'waiting'])
                          ->where('priority', 'high');
                }
            ])
            ->having('active_conversations_count', '<', self::MAX_CONVERSATIONS_PER_AGENT);

        // If conversation has skill requirements, filter by skills
        if ($conversation->preferred_language && $conversation->preferred_domain) {
            // Both language AND domain are required - agent must have BOTH skills
            $baseQuery->whereHas('skills', function ($langQuery) use ($conversation) {
                $langQuery->where('skill_type', 'language')
                        ->where('skill_code', $conversation->preferred_language);
            })
            ->whereHas('skills', function ($domainQuery) use ($conversation) {
                $domainQuery->where('skill_type', 'domain')
                          ->where('skill_code', $conversation->preferred_domain);
            });
        } elseif ($conversation->preferred_language) {
            // Only language requirement
            $baseQuery->whereHas('skills', function ($skillQuery) use ($conversation) {
                $skillQuery->where('skill_type', 'language')
                          ->where('skill_code', $conversation->preferred_language);
            });
        } elseif ($conversation->preferred_domain) {
            // Only domain requirement
            $baseQuery->whereHas('skills', function ($skillQuery) use ($conversation) {
                $skillQuery->where('skill_type', 'domain')
                          ->where('skill_code', $conversation->preferred_domain);
            });
        }

        $availableAgents = $baseQuery->with(['skills'])->get();

        if ($availableAgents->isEmpty()) {
            Log::info("No skilled agents available within capacity limits for language: {$conversation->preferred_language}, domain: {$conversation->preferred_domain}");
            
            // Return null instead of fallback - let the parent method handle fallback logic
            return null;
        }

        // Filter by priority capacity for high priority conversations
        if ($priority === 'high') {
            $availableAgents = $availableAgents->filter(function ($agent) {
                return $agent->high_priority_count < self::HIGH_PRIORITY_QUEUE_LIMIT;
            });
        }

        if ($availableAgents->isEmpty()) {
            Log::info("No skilled agents available for priority: {$priority}");
            return null;
        }

        // Calculate skill match scores and sort by best fit
        $scoredAgents = $availableAgents->map(function ($agent) use ($conversation) {
            $agent->skill_match_score = $agent->getSkillMatchScore(
                $conversation->preferred_language,
                $conversation->preferred_domain
            );
            return $agent;
        });

        // Sort by: skill match score (desc), conversation count (asc)
        $bestAgent = $scoredAgents->sortByDesc('skill_match_score')
            ->sortBy('active_conversations_count')
            ->first();

        // Update conversation with match scores
        if ($bestAgent) {
            $conversation->update([
                'language_match_score' => $bestAgent->hasLanguageSkill($conversation->preferred_language) ? 
                    $bestAgent->languageSkills()->where('skill_code', $conversation->preferred_language)->first()?->proficiency_level ?? 0 : 0,
                'domain_match_score' => $bestAgent->hasDomainSkill($conversation->preferred_domain) ? 
                    $bestAgent->domainSkills()->where('skill_code', $conversation->preferred_domain)->first()?->proficiency_level ?? 0 : 0
            ]);
        }

        return $bestAgent;
    }

    /**
     * Enhanced agent selection with capacity and priority management (fallback without skills)
     */
    private function findBestAvailableAgent($priority = 'normal'): ?Agent
    {
        // Get agents who are online and not at capacity - only check online status
        $availableAgents = Agent::where('status', 'online')
            ->withCount([
                'conversations as active_conversations_count' => function ($query) {
                    $query->whereIn('status', ['active', 'waiting']);
                },
                'conversations as high_priority_count' => function ($query) {
                    $query->whereIn('status', ['active', 'waiting'])
                          ->where('priority', 'high');
                }
            ])
            ->having('active_conversations_count', '<', self::MAX_CONVERSATIONS_PER_AGENT)
            ->get();

        if ($availableAgents->isEmpty()) {
            Log::info("No agents available within capacity limits");
            return null;
        }

        // Filter by priority capacity for high priority conversations
        if ($priority === 'high') {
            $availableAgents = $availableAgents->filter(function ($agent) {
                return $agent->high_priority_count < self::HIGH_PRIORITY_QUEUE_LIMIT;
            });
        }

        if ($availableAgents->isEmpty()) {
            Log::info("No agents available for priority: {$priority}");
            return null;
        }

        // Sort by workload and experience
        return $availableAgents->sortBy([
            ['active_conversations_count', 'asc'],
            ['high_priority_count', 'asc']
        ])->first();
    }

    /**
     * Add conversation to waiting queue with priority
     */
    private function addToWaitingQueue(Conversation $conversation, $priority = 'normal'): void
    {
        $conversation->update([
            'status' => 'waiting',
            'priority' => $priority,
            'last_activity' => now()
        ]);

        // Create system message to inform user they're in queue
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => "All our agents are currently busy. You've been added to the queue and will be connected to the next available agent.",
            'sender_type' => 'system',
            'sender_id' => null,
        ]);

        Log::info("Added conversation {$conversation->id} to waiting queue with priority: {$priority}");
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

        // Broadcast the assignment to all agents so they can update their lists
        broadcast(new \App\Events\NewConversationEvent($conversation->fresh()));
        
        // Broadcast assignment to the user so they know an agent picked up
        broadcast(new \App\Events\AgentTransferNotification($conversation, null, $agent, 'agent_assigned'));
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
            // Generate handoff summary for the new agent
            $handoffSummary = $this->generateHandoffSummary($conversation, $agent);
            
            // Store original agent info before reassignment
            $originalAgent = $agent;
            
            // Mark conversation as transferred
            $conversation->update([
                'agent_id' => null,
                'status' => 'waiting',
                'last_activity' => now(),
                'priority' => 'high', // Mark as high priority due to transfer
                'is_transferred' => true,
                'transfer_count' => $conversation->transfer_count + 1,
                'last_transferred_at' => now()
            ]);

            // Notify customer about agent transfer (before finding new agent)
            broadcast(new AgentTransferNotification($conversation, $originalAgent, null, 'agent_disconnect'));
            
            // Try to reassign immediately - first to skilled agents, then to any available agent
            $newAgent = $this->findBestAgentForTransfer($conversation, 'high');
            
            if ($newAgent) {
                $conversation->update([
                    'agent_id' => $newAgent->id,
                    'status' => 'active'
                ]);
                
                // Send customer notification with new agent info
                broadcast(new AgentTransferNotification($conversation, $originalAgent, $newAgent, 'agent_disconnect'));
                
                // Notify new agent with handoff summary
                broadcast(new AgentHandoffNotification($conversation, $newAgent, $handoffSummary));
                
                // Create system message introducing new agent
                $introMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'content' => "Hi! I'm {$newAgent->name} and I'll be continuing to assist you. {$originalAgent->name} had to step away, but I have your full conversation history and I'm here to help!",
                    'sender_type' => 'system',
                    'sender_id' => null,
                ]);
                
                // Broadcast the intro message
                broadcast(new NewMessageEvent($introMessage));
                
                // Ensure unread count only includes user messages
                $conversation->updateUnreadCount();
                
                $redistributedCount++;
                
                Log::info("Successfully transferred conversation {$conversation->id} from {$originalAgent->name} to {$newAgent->name}");
            } else {
                // No agents available immediately - send user message and make available to all agents
                $waitingMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'content' => 'Your previous agent had to step away. We\'re finding another qualified agent to assist you. Please hold on for a moment.',
                    'sender_type' => 'system',
                    'sender_id' => null,
                ]);
                
                // Broadcast the waiting message
                broadcast(new NewMessageEvent($waitingMessage));
                
                // Send transfer notification to user indicating waiting status
                broadcast(new AgentTransferNotification($conversation, $originalAgent, null, 'finding_agent'));
                
                // Broadcast to agents that a new conversation is available for pickup
                broadcast(new \App\Events\NewConversationEvent($conversation->fresh()));
                
                // Ensure unread count only includes user messages
                $conversation->updateUnreadCount();
                
                Log::warning("No available agents for conversation {$conversation->id} transfer - conversation marked as waiting for any agent");
            }
        }

        Log::info("Redistributed {$redistributedCount} conversations from offline agent {$agent->id}");

        return $redistributedCount;
    }

    /**
     * Generate a comprehensive handoff summary for the new agent
     */
    private function generateHandoffSummary(Conversation $conversation, Agent $originalAgent): array
    {
        $messageCount = $conversation->messages()->count();
        $duration = $conversation->created_at->diffInMinutes(now());
        $lastMessage = $conversation->messages()->latest()->first();
        
        return [
            'customer_name' => $conversation->user->name ?? 'Customer',
            'customer_email' => $conversation->user->email ?? 'N/A',
            'conversation_topic' => $conversation->topic ?? 'General inquiry',
            'conversation_duration' => $duration . ' minutes',
            'message_count' => $messageCount,
            'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : 'No messages',
            'last_message_preview' => $lastMessage ? substr($lastMessage->content, 0, 100) . '...' : 'No messages yet',
            'original_agent' => $originalAgent->name,
            'transfer_reason' => 'agent_disconnect',
            'priority_level' => 'high',
            'customer_status' => $this->getCustomerStatus($conversation),
            'suggested_actions' => $this->getSuggestedActions($conversation)
        ];
    }

    /**
     * Determine customer status based on conversation history
     */
    private function getCustomerStatus(Conversation $conversation): string
    {
        $messageCount = $conversation->messages()->count();
        $duration = $conversation->created_at->diffInMinutes(now());
        
        if ($duration > 30) {
            return 'Long conversation - may need escalation';
        } elseif ($messageCount > 20) {
            return 'High engagement - complex issue';
        } elseif ($messageCount < 3) {
            return 'New conversation - just started';
        } else {
            return 'Standard conversation';
        }
    }

    /**
     * Generate suggested actions for the new agent
     */
    private function getSuggestedActions(Conversation $conversation): array
    {
        $actions = [];
        $messageCount = $conversation->messages()->count();
        $duration = $conversation->created_at->diffInMinutes(now());
        
        // Standard greeting
        $actions[] = 'Introduce yourself and acknowledge the transfer';
        
        // Based on conversation length
        if ($messageCount > 10) {
            $actions[] = 'Review conversation history for context';
            $actions[] = 'Summarize understanding of the issue';
        }
        
        // Based on duration
        if ($duration > 20) {
            $actions[] = 'Apologize for the wait and transfer';
            $actions[] = 'Consider escalation if needed';
        }
        
        // Always include
        $actions[] = 'Ask how you can best assist them';
        
        return $actions;
    }

    /**
     * Find the best agent for transferring a conversation
     * First tries skilled agents, then falls back to any available agent
     */
    private function findBestAgentForTransfer(Conversation $conversation, string $priority = 'normal'): ?Agent
    {
        // First try to find skilled agents
        $skilledAgent = $this->findBestAvailableAgentWithSkills($conversation, $priority);
        
        if ($skilledAgent) {
            Log::info("Found skilled agent {$skilledAgent->id} for transfer of conversation {$conversation->id}");
            return $skilledAgent;
        }
        
        // Only fallback to basic assignment if no specific skills are required
        // If both language AND domain are specified, we require strict skill matching even for transfers
        if (!$conversation->preferred_language && !$conversation->preferred_domain) {
            $anyAgent = $this->findBestAvailableAgent($priority);
            
            if ($anyAgent) {
                Log::info("Found general agent {$anyAgent->id} for transfer of conversation {$conversation->id} (no skill requirements)");
                return $anyAgent;
            }
        } else {
            Log::warning("No skilled agents available for transfer of conversation {$conversation->id} with language: {$conversation->preferred_language}, domain: {$conversation->preferred_domain} - maintaining skill requirements");
        }
        
        Log::warning("No suitable agents available for transfer of conversation {$conversation->id}");
        return null;
    }
}
