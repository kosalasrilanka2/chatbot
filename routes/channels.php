<?php

use App\Models\Agent;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) {
        return false;
    }
    
    // Allow user who owns the conversation
    if ($conversation->user_id === $user->id) {
        return true;
    }
    
    // Allow agent assigned to the conversation
    $agent = Agent::where('email', $user->email)->first();
    if ($agent && $conversation->agent_id === $agent->id) {
        return true;
    }
    
    // Allow any agent to join unassigned conversations
    if ($agent && !$conversation->agent_id) {
        return true;
    }
    
    return false;
});

Broadcast::channel('agent.{agentId}', function ($user, $agentId) {
    $agent = Agent::where('email', $user->email)->first();
    return $agent && $agent->id === (int) $agentId;
});

Broadcast::channel('agents', function ($user) {
    // Allow all authenticated users to listen to agent status updates
    return true;
});
