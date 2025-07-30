<?php

namespace App\Http\Controllers;

use App\Events\NewMessageEvent;
use App\Models\Agent;
use App\Models\AgentSkill;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat.index');
    }

    public function userChat()
    {
        return view('chat.user');
    }

    public function getUserConversations()
    {
        $conversations = Conversation::with(['messages' => function($query) {
            $query->latest()->limit(1);
        }, 'agent'])
        ->where('user_id', Auth::user()->id)
        ->orderBy('last_activity', 'desc')
        ->get();

        return response()->json($conversations);
    }

    public function getConversation($conversationId)
    {
        $conversation = Conversation::with(['messages.sender', 'user', 'agent'])
            ->findOrFail($conversationId);

        return response()->json($conversation);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string',
            'sender_type' => 'required|in:user,agent,system'
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        // Determine sender_id based on sender_type
        $senderId = null;
        if ($request->sender_type === 'user') {
            $senderId = Auth::user()->id;
        } elseif ($request->sender_type === 'agent') {
            $agent = Agent::where('email', Auth::user()->email)->first();
            $senderId = $agent ? $agent->id : null;
        }

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'content' => $request->content,
            'sender_type' => $request->sender_type,
            'sender_id' => $senderId,
        ]);

        // Update conversation last activity
        $conversation->update(['last_activity' => now()]);

        // Broadcast the message
        broadcast(new NewMessageEvent($message));

        // Return structured response with message data
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

    public function markAsRead(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id'
        ]);

        Message::whereIn('id', $request->message_ids)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    public function createConversation(Request $request)
    {
        try {
            $request->validate([
                'title' => 'nullable|string',
                'preferred_language' => 'nullable|string|in:SI,TI,EN',
                'preferred_domain' => 'nullable|string|in:FINANCE,HR,IT,NETWORK',
            ]);

            $conversation = Conversation::create([
                'title' => $request->title ?? 'New Conversation',
                'user_id' => Auth::user()->id,
                'status' => 'waiting',
                'preferred_language' => $request->preferred_language,
                'preferred_domain' => $request->preferred_domain,
                'last_activity' => now()
            ]);

            // Try to auto-assign to an available agent with skill matching
            $assignmentService = new ConversationAssignmentService();
            $assignedAgent = $assignmentService->autoAssignConversation($conversation);

            // Simple skill info without using AgentSkill constants temporarily
            $languageName = $request->preferred_language;
            $domainName = $request->preferred_domain;

            $skillInfo = '';
            if ($languageName && $domainName) {
                $skillInfo = " (Language: {$languageName}, Domain: {$domainName})";
            } elseif ($languageName) {
                $skillInfo = " (Language: {$languageName})";
            } elseif ($domainName) {
                $skillInfo = " (Domain: {$domainName})";
            }

            return response()->json([
                'conversation' => $conversation->fresh(), // Get updated conversation data
                'auto_assigned' => $assignedAgent ? true : false,
                'agent_name' => $assignedAgent ? $assignedAgent->name : null,
                'skill_match' => $assignedAgent && ($languageName || $domainName),
                'message' => $assignedAgent 
                    ? "Conversation created and assigned to {$assignedAgent->name}{$skillInfo}" 
                    : "Conversation created{$skillInfo}. Waiting for a suitable agent."
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating conversation: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to create conversation',
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function getMessages($conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Transform messages to include sender name
        $messages = $messages->map(function ($message) {
            $message->sender_name = $message->getSenderAttribute();
            return $message;
        });

        return response()->json($messages);
    }
}
