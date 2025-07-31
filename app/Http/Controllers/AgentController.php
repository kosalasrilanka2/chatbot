<?php

namespace App\Http\Controllers;

use App\Events\AgentStatusUpdated;
use App\Events\NewMessageEvent;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    public function index()
    {
        $agent = Agent::with('skills')->where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            // If no agent found, redirect to a different page or show an error
            return redirect()->route('dashboard')->with('error', 'Agent profile not found. Please contact administrator.');
        }
        
        // Automatically set agent status to online when they access the dashboard
        $agent->update([
            'status' => 'online',
            'last_seen' => now()
        ]);
        
        return view('agent.dashboard', compact('agent'));
    }

    public function conversations()
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            Log::info('Agent not found for email: ' . Auth::user()->email);
            return response()->json(['error' => 'Agent not found'], 404);
        }

        Log::info('Agent found: ' . $agent->id . ' for email: ' . Auth::user()->email);

        // Get assigned conversations (both active and closed)
        $assignedConversations = Conversation::with(['user', 'messages' => function($query) {
            $query->latest()->limit(1);
        }])
        ->where('agent_id', $agent->id)
        ->whereIn('status', ['active', 'closed']) // Include closed conversations
        ->orderBy('last_activity', 'desc')
        ->get();

        // Ensure unread counts are accurate for assigned conversations
        $assignedConversations->each(function($conversation) {
            $conversation->recalculateUnreadCount();
        });

        Log::info('Assigned conversations count: ' . $assignedConversations->count());

        // Get waiting conversations that can be manually picked up
        // Show all waiting conversations to all agents, regardless of skills
        $waitingConversations = Conversation::with(['user', 'messages' => function($query) {
            $query->latest()->limit(1);
        }])
        ->where('status', 'waiting')
        ->whereNull('agent_id')
        ->orderBy('priority', 'desc') // Show high priority first
        ->orderBy('last_activity', 'desc') // Then by latest activity
        ->limit(20) // Show max 20 waiting conversations
        ->get();

        Log::info('Waiting conversations count: ' . $waitingConversations->count());

        return response()->json([
            'assigned_conversations' => $assignedConversations,
            'waiting_conversations' => $waitingConversations,
            'stats' => [
                'assigned_count' => $assignedConversations->count(),
                'waiting_count' => $waitingConversations->count(),
                'total_waiting' => Conversation::where('status', 'waiting')->whereNull('agent_id')->count()
            ]
        ]);
    }

    public function updateStatus(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $request->validate([
            'status' => 'required|in:online,busy'
        ]);

        $previousStatus = $agent->status;
        
        $agent->update([
            'status' => $request->status,
            'last_seen' => now()
        ]);

        broadcast(new AgentStatusUpdated($agent));

        // Handle automatic assignment when agent comes online
        $assignmentService = new ConversationAssignmentService();
        $assignedCount = 0;

        if ($request->status === 'online' && $previousStatus !== 'online') {
            // Agent just came online, try to assign waiting conversations
            $assignedCount = $assignmentService->processWaitingConversationsForAgent($agent);
        } elseif ($previousStatus === 'online' && $request->status === 'offline') {
            // Agent went offline, redistribute their conversations
            $redistributedCount = $assignmentService->redistributeConversationsFromOfflineAgent($agent);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'assigned_conversations' => $assignedCount,
            'agent_status' => $request->status
        ]);
    }

    public function assignConversation(Request $request, Conversation $conversation)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Check if agent is busy
        if ($agent->status === 'busy') {
            return response()->json(['error' => 'Cannot pick up new conversations while in busy mode. Please change your status to online first.'], 403);
        }

        // Check if this is a transferred conversation
        $wasTransferred = $conversation->is_transferred;
        $oldAgent = $conversation->agent_id ? Agent::find($conversation->agent_id) : null;

        $conversation->update([
            'agent_id' => $agent->id,
            'status' => 'active'
        ]);

        // Always notify the user when an agent picks up (whether new assignment or transfer)
        if ($wasTransferred) {
            // Send transfer completion notification
            broadcast(new \App\Events\AgentTransferNotification($conversation, $oldAgent, $agent, 'agent_assigned'));
            
            // Create system message for transfer
            $assignmentMessage = \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'content' => "Hi! I'm {$agent->name} and I'll be continuing to assist you. I have your full conversation history and I'm here to help!",
                'sender_type' => 'system',
                'sender_id' => null,
            ]);
            
            // Broadcast the assignment message
            broadcast(new \App\Events\NewMessageEvent($assignmentMessage));
        } else {
            // Regular assignment - notify user that agent picked up
            broadcast(new \App\Events\AgentTransferNotification($conversation, null, $agent, 'agent_assigned'));
            
            // Create system message for regular assignment
            $assignmentMessage = \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'content' => "Hi! I'm {$agent->name} and I'm here to help you. How can I assist you today?",
                'sender_type' => 'system',
                'sender_id' => null,
            ]);
            
            // Broadcast the assignment message
            broadcast(new \App\Events\NewMessageEvent($assignmentMessage));
        }

        // Broadcast the assignment to all agents so they can update their lists
        broadcast(new \App\Events\NewConversationEvent($conversation->fresh()));

        return response()->json(['message' => 'Conversation assigned successfully']);
    }

    public function heartbeat(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Update last_seen timestamp and status
        $agent->update([
            'last_seen' => now(),
            'status' => $request->input('status', $agent->status)
        ]);

        return response()->json([
            'message' => 'Heartbeat received',
            'timestamp' => now()->toISOString()
        ]);
    }

    public function setOffline(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $reason = $request->input('reason', 'manual');
        
        // Set agent offline
        $agent->update([
            'status' => 'offline',
            'last_seen' => now()
        ]);

        // Broadcast status change
        broadcast(new \App\Events\AgentStatusUpdated($agent));

        // If this was due to disconnection, redistribute their conversations
        if (in_array($reason, ['browser_close', 'network_disconnect', 'timeout'])) {
            $assignmentService = new ConversationAssignmentService();
            $redistributedCount = $assignmentService->redistributeConversationsFromOfflineAgent($agent);
            
            return response()->json([
                'message' => 'Agent set offline',
                'reason' => $reason,
                'redistributed_conversations' => $redistributedCount
            ]);
        }

        return response()->json([
            'message' => 'Agent set offline',
            'reason' => $reason
        ]);
    }

    public function forceLogout(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $reason = $request->input('reason', 'inactivity');
        
        // Set agent offline
        $agent->update([
            'status' => 'offline',
            'last_seen' => now()
        ]);

        // Redistribute their conversations
        $assignmentService = new ConversationAssignmentService();
        $redistributedCount = $assignmentService->redistributeConversationsFromOfflineAgent($agent);

        // Broadcast status change
        broadcast(new \App\Events\AgentStatusUpdated($agent));

        // Perform the actual logout
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Agent logged out due to inactivity',
            'reason' => $reason,
            'redistributed_conversations' => $redistributedCount,
            'redirect' => '/'
        ]);
    }

    public function getUnreadCount()
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Recalculate unread counts for all agent's conversations to ensure accuracy
        $conversations = Conversation::where('agent_id', $agent->id)->get();
        $totalUnread = 0;
        
        foreach ($conversations as $conversation) {
            $totalUnread += $conversation->recalculateUnreadCount();
        }

        return response()->json(['unread_count' => $totalUnread]);
    }

    public function getConversation(Conversation $conversation)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Only allow access if:
        // 1. Conversation is assigned to this agent
        // 2. Conversation is unassigned (for assignment purposes)
        if ($conversation->agent_id !== null && $conversation->agent_id !== $agent->id) {
            return response()->json(['error' => 'This conversation is assigned to another agent'], 403);
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

        // Only allow access if:
        // 1. Conversation is assigned to this agent
        // 2. Conversation is unassigned (agent_id is null) - for assignment purposes
        if ($conversation->agent_id !== null && $conversation->agent_id !== $agent->id) {
            return response()->json(['error' => 'This conversation is assigned to another agent'], 403);
        }

        // If conversation is unassigned, assign it to this agent when they access messages
        if ($conversation->agent_id === null) {
            $conversation->update([
                'agent_id' => $agent->id,
                'status' => 'active'  // Change status from 'waiting' to 'active'
            ]);
        }

        // Mark all user messages in this conversation as read
        $conversation->markAllAsRead();

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

        // Check if agent is busy
        if ($agent->status === 'busy') {
            return response()->json(['error' => 'Cannot send messages while in busy mode. Please change your status to online first.'], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::find($request->conversation_id);

        // Check if conversation is closed
        if ($conversation->status === 'closed') {
            return response()->json(['error' => 'Cannot send messages to closed conversations'], 400);
        }

        // Only allow replies if conversation is assigned to this agent
        if ($conversation->agent_id !== $agent->id) {
            return response()->json(['error' => 'Cannot reply to unassigned conversations. Please assign the conversation first.'], 403);
        }

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'content' => $request->content,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
        ]);

        // Mark all user messages in this conversation as read since agent is replying
        $conversation->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // Update conversation last activity and reset unread count
        // Agent is actively replying, so all messages are considered read
        $conversation->update([
            'last_activity' => now(),
            'unread_count' => 0
        ]);

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

    public function logout(Request $request)
    {
        $agent = Agent::where('email', Auth::user()->email)->first();
        
        if ($agent) {
            // Set agent status to offline before logout
            $agent->update([
                'status' => 'offline',
                'last_seen' => now()
            ]);
            
            // Broadcast status change
            broadcast(new AgentStatusUpdated($agent));
            
            // Handle conversation redistribution if needed
            $assignmentService = new ConversationAssignmentService();
            $assignmentService->redistributeConversationsFromOfflineAgent($agent);
        }
        
        // Perform the actual logout
        Auth::guard('web')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}
