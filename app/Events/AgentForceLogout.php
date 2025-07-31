<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentForceLogout implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $agent;

    /**
     * Create a new event instance.
     */
    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
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
        return 'force-logout';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'You have been logged out due to inactivity',
            'reason' => 'inactivity',
            'timestamp' => now()->toISOString()
        ];
    }
}
