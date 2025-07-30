<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function getStats()
    {
        // Get currently logged in users (users who have been active in last 15 minutes)
        $activeUsers = User::where('updated_at', '>=', now()->subMinutes(15))
            ->select('id', 'name', 'email', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_activity' => $user->updated_at,
                    'status' => 'online',
                    'type' => 'user'
                ];
            });

        // Get currently active agents
        $activeAgents = Agent::where('last_seen', '>=', now()->subMinutes(15))
            ->orWhere('status', 'online')
            ->select('id', 'name', 'email', 'status', 'last_seen')
            ->orderBy('last_seen', 'desc')
            ->get()
            ->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'last_activity' => $agent->last_seen,
                    'status' => $agent->status,
                    'type' => 'agent'
                ];
            });

        // Get general statistics
        $stats = [
            'total_users' => User::count(),
            'total_agents' => Agent::count(),
            'active_conversations' => Conversation::where('status', 'active')->count(),
            'waiting_conversations' => Conversation::where('status', 'waiting')->whereNull('agent_id')->count(),
            'total_messages_today' => Message::whereDate('created_at', today())->count(),
            'unread_messages' => Message::where('is_read', false)->count(),
        ];

        // Get assignment statistics
        $assignmentService = new ConversationAssignmentService();
        $assignmentStats = $assignmentService->getAssignmentStats();

        // Get recent conversations
        $recentConversations = Conversation::with(['user', 'messages' => function($query) {
            $query->latest()->limit(1);
        }])
        ->orderBy('last_activity', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($conversation) {
            $lastMessage = $conversation->messages->first();
            return [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'user_name' => $conversation->user->name,
                'user_email' => $conversation->user->email,
                'status' => $conversation->status,
                'last_activity' => $conversation->last_activity,
                'last_message' => $lastMessage ? $lastMessage->content : 'No messages',
                'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
            ];
        });

        return response()->json([
            'active_users' => $activeUsers,
            'active_agents' => $activeAgents,
            'stats' => $stats,
            'assignment_stats' => $assignmentStats,
            'recent_conversations' => $recentConversations,
        ]);
    }

    public function getOnlineUsers()
    {
        // This endpoint specifically for real-time updates
        $onlineUsers = User::where('updated_at', '>=', now()->subMinutes(5))
            ->select('id', 'name', 'email', 'updated_at')
            ->get();

        $onlineAgents = Agent::where('status', 'online')
            ->orWhere('last_seen', '>=', now()->subMinutes(5))
            ->select('id', 'name', 'email', 'status', 'last_seen')
            ->get();

        return response()->json([
            'users' => $onlineUsers,
            'agents' => $onlineAgents,
            'timestamp' => now()
        ]);
    }
}
