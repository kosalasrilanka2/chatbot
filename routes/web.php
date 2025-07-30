<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/echo-test', function () {
    return view('echo-test');
})->name('echo.test');

Route::get('/echo-debug', function () {
    return view('echo-debug');
})->middleware('auth')->name('echo.debug');

Route::post('/chat/debug-broadcast', function () {
    try {
        $conversation = \App\Models\Conversation::first();
        if (!$conversation) {
            return response()->json(['error' => 'No conversation found']);
        }

        $message = \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Debug broadcast at ' . now()->format('H:i:s'),
            'sender_type' => 'system',
            'sender_id' => null,
        ]);

        broadcast(new \App\Events\NewMessageEvent($message));

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'channels' => ['conversation.' . $conversation->id, 'agent.' . $conversation->agent_id]
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
})->middleware('auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Chat routes
Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/conversations', [ChatController::class, 'getUserConversations'])->name('chat.conversations');
    Route::post('/chat/conversation', [ChatController::class, 'createConversation'])->name('chat.create-conversation');
    Route::get('/chat/conversation/{conversation}', [ChatController::class, 'getConversation'])->name('chat.get-conversation');
    Route::get('/chat/conversation/{conversation}/messages', [ChatController::class, 'getMessages'])->name('chat.get-messages');
    Route::post('/chat/message', [ChatController::class, 'sendMessage'])->name('chat.send-message');
    Route::post('/chat/mark-read', [ChatController::class, 'markAsRead'])->name('chat.mark-read');
    
    // Debug route
    Route::get('/chat/debug', function() {
        return response()->json([
            'user' => Auth::user(),
            'csrf' => csrf_token(),
            'routes' => [
                'create_conversation' => route('chat.create-conversation'),
                'send_message' => route('chat.send-message')
            ]
        ]);
    })->name('chat.debug');
    
    // Test messages endpoint
    Route::get('/chat/test-messages/{conversationId}', function($conversationId) {
        try {
            $messages = \App\Models\Message::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')
                ->get();

            $transformedMessages = $messages->map(function ($message) {
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

            return response()->json([
                'status' => 'success',
                'count' => $transformedMessages->count(),
                'messages' => $transformedMessages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
});

// Agent routes
Route::middleware('auth')->prefix('agent')->group(function () {
    Route::get('/dashboard', [AgentController::class, 'index'])->name('agent.dashboard');
    Route::get('/conversations', [AgentController::class, 'conversations'])->name('agent.conversations');
    Route::get('/conversation/{conversation}', [AgentController::class, 'getConversation'])->name('agent.get-conversation');
    Route::get('/conversation/{conversation}/messages', [AgentController::class, 'getMessages'])->name('agent.get-messages');
    Route::post('/message', [AgentController::class, 'sendMessage'])->name('agent.send-message');
    Route::post('/status', [AgentController::class, 'updateStatus'])->name('agent.update-status');
    Route::post('/conversation/{conversation}/assign', [AgentController::class, 'assignConversation'])->name('agent.assign-conversation');
    Route::get('/unread-count', [AgentController::class, 'getUnreadCount'])->name('agent.unread-count');
});

require __DIR__.'/auth.php';
