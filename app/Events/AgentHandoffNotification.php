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

class AgentHandoffNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $agent;
    public $handoffSummary;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation, Agent $agent, array $handoffSummary = [])
    {
        $this->conversation = $conversation;
        $this->agent = $agent;
        $this->handoffSummary = $handoffSummary;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agent.' . $this->agent->id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation-handoff';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'handoff_summary' => $this->handoffSummary,
            'alert_message' => "New chat transferred - {$this->conversation->user->name}",
            'suggested_greeting' => $this->getSuggestedGreeting(),
            'priority' => 'high',
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Generate suggested greeting for the new agent
     */
    private function getSuggestedGreeting(): string
    {
        $customerName = $this->conversation->user->name ?? 'there';
        $transferReason = $this->handoffSummary['transfer_reason'] ?? 'agent transfer';
        
        if ($transferReason === 'agent_disconnect') {
            return "Hi {$customerName}! I'm {$this->agent->name} and I'll be continuing to help you. I can see your conversation history and I'm here to assist you.";
        }
        
        return "Hello {$customerName}! I'm {$this->agent->name} and I'll be taking over to help you today.";
    }
}
