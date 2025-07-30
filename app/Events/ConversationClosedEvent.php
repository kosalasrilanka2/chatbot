<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationClosedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation->load(['user', 'agent']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('agents'), // Broadcast to all agents
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.closed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'title' => $this->conversation->title,
                'user' => [
                    'id' => $this->conversation->user->id,
                    'name' => $this->conversation->user->name,
                ],
                'agent' => $this->conversation->agent ? [
                    'id' => $this->conversation->agent->id,
                    'name' => $this->conversation->agent->name,
                ] : null,
                'status' => $this->conversation->status,
                'preferred_language' => $this->conversation->preferred_language,
                'preferred_domain' => $this->conversation->preferred_domain,
                'last_activity' => $this->conversation->last_activity,
                'closed_at' => $this->conversation->updated_at,
            ]
        ];
    }
}
