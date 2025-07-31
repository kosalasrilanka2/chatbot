<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Agent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentTransferNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $oldAgent;
    public $newAgent;
    public $transferReason;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, Agent $oldAgent = null, Agent $newAgent = null, string $transferReason = 'agent_disconnect')
    {
        $this->conversation = $conversation;
        $this->oldAgent = $oldAgent;
        $this->newAgent = $newAgent;
        $this->transferReason = $transferReason;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation->id),
            new PrivateChannel('user.' . $this->conversation->user_id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'agent-transfer';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'old_agent' => $this->oldAgent ? [
                'id' => $this->oldAgent->id,
                'name' => $this->oldAgent->name
            ] : null,
            'new_agent' => $this->newAgent ? [
                'id' => $this->newAgent->id,
                'name' => $this->newAgent->name
            ] : null,
            'transfer_reason' => $this->transferReason,
            'message' => $this->getTransferMessage(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get the appropriate transfer message for customers
     */
    private function getTransferMessage(): string
    {
        if ($this->newAgent) {
            $oldAgentName = $this->oldAgent ? $this->oldAgent->name : 'your previous agent';
            
            switch ($this->transferReason) {
                case 'agent_assigned':
                    return "Great news! {$this->newAgent->name} is now attending to your conversation and will continue assisting you.";
                default:
                    return "Hi! I'm {$this->newAgent->name} and I'll be continuing to assist you. {$oldAgentName} had to step away, but I have your full conversation history and I'm here to help!";
            }
        }

        // Handle different transfer reasons when no new agent is assigned yet
        switch ($this->transferReason) {
            case 'finding_agent':
                return "Finding a new agent to assist you. Please hold on...";
            case 'agent_disconnect':
                return "Your agent had to step away temporarily. We're finding another qualified agent to continue assisting you.";
            default:
                return "We're connecting you with another available agent who will continue assisting you. Please hold on...";
        }
    }
}
