<?php

namespace App\Http\Controllers;

use App\Events\AgentStatusUpdated;
use App\Events\NewMessageEvent;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    public function index()
    {
        return view('agent.dashboard');
    }

    public function conversations()
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $conversations = Conversation::with(['user', 'messages' => function($query) {
            $query->latest()->limit(1);
        }])
        ->where('agent_id', $agent->id)
        ->orWhereNull('agent_id')
        ->orderBy('last_activity', 'desc')
        ->get();

        return response()->json($conversations);
    }

    public function updateStatus(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $request->validate([
            'status' => 'required|in:online,offline,busy'
        ]);

        $agent->update([
            'status' => $request->status,
            'last_seen' => now()
        ]);

        broadcast(new AgentStatusUpdated($agent));

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function assignConversation(Request $request, Conversation $conversation)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $conversation->update([
            'agent_id' => $agent->id,
            'status' => 'active'
        ]);

        return response()->json(['message' => 'Conversation assigned successfully']);
    }

    public function getUnreadCount()
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $unreadCount = Message::whereHas('conversation', function($query) use ($agent) {
            $query->where('agent_id', $agent->id);
        })
        ->where('sender_type', 'user')
        ->where('is_read', false)
        ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }

    public function getConversation(Conversation $conversation)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Check if agent has access to this conversation
        if ($conversation->agent_id !== $agent->id && $conversation->agent_id !== null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->load(['user', 'agent']);
        return response()->json($conversation);
    }

    public function getMessages(Conversation $conversation)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Check if agent has access to this conversation
        if ($conversation->agent_id !== $agent->id && $conversation->agent_id !== null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'content' => $message->content,
                    'sender_type' => $message->sender_type,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->getSenderAttribute(),
                    'created_at' => $message->created_at,
                    'is_read' => $message->is_read,
                ];
            });

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::find($request->conversation_id);

        // Check if agent has access to this conversation
        if ($conversation->agent_id !== $agent->id && $conversation->agent_id !== null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Assign conversation to agent if unassigned
        if (!$conversation->agent_id) {
            $conversation->update(['agent_id' => $agent->id]);
        }

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'content' => $request->content,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
        ]);

        // Update conversation last activity
        $conversation->update(['last_activity' => now()]);

        // Broadcast the message
        broadcast(new \App\Events\NewMessageEvent($message));

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'content' => $message->content,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->getSenderAttribute(),
                'created_at' => $message->created_at,
                'is_read' => $message->is_read,
            ]
        ]);
    }
}
