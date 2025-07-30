<?php

namespace App\Http\Controllers;

use App\Events\NewMessageEvent;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat.index');
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
        $request->validate([
            'title' => 'nullable|string',
        ]);

        $conversation = Conversation::create([
            'title' => $request->title ?? 'New Conversation',
            'user_id' => Auth::user()->id,
            'status' => 'waiting',
            'last_activity' => now()
        ]);

        return response()->json($conversation);
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
