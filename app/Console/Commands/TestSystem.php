<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\NewMessageEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete chatbot system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Chatbot System...');
        $this->newLine();

        // Test Database Connection
        $this->info('📊 Testing Database Connection...');
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            return 1;
        }

        // Test Model Counts
        $this->info('📈 Model Counts:');
        $this->table(
            ['Model', 'Count'],
            [
                ['Users', User::count()],
                ['Agents', Agent::count()],
                ['Conversations', Conversation::count()],
                ['Messages', Message::count()],
            ]
        );

        // Test Routes
        $this->info('🛣️  Testing Routes...');
        $routes = [
            'chat.index' => route('chat.index'),
            'chat.conversations' => route('chat.conversations'),
            'agent.dashboard' => route('agent.dashboard'),
            'agent.conversations' => route('agent.conversations'),
        ];

        foreach ($routes as $name => $url) {
            $this->info("✅ {$name}: {$url}");
        }

        // Test Sample Data
        $this->info('👥 Sample Users:');
        $users = User::take(3)->get(['id', 'name', 'email']);
        $this->table(['ID', 'Name', 'Email'], $users->toArray());

        $this->info('🤖 Sample Agents:');
        $agents = Agent::take(3)->get(['id', 'name', 'email', 'status']);
        $this->table(['ID', 'Name', 'Email', 'Status'], $agents->toArray());

        // Test Recent Activity
        $this->info('💬 Recent Conversations:');
        $conversations = Conversation::with(['user', 'agent'])
            ->latest('last_activity')
            ->take(5)
            ->get(['id', 'title', 'user_id', 'agent_id', 'status', 'last_activity']);

        $conversationData = $conversations->map(function ($conv) {
            return [
                'ID' => $conv->id,
                'Title' => $conv->title ?? 'N/A',
                'User' => $conv->user->name ?? 'N/A',
                'Agent' => $conv->agent->name ?? 'Unassigned',
                'Status' => $conv->status,
                'Last Activity' => $conv->last_activity?->format('Y-m-d H:i:s') ?? 'N/A'
            ];
        });

        $this->table(['ID', 'Title', 'User', 'Agent', 'Status', 'Last Activity'], $conversationData->toArray());

        // Test Recent Messages
        $this->info('📨 Recent Messages:');
        $messages = Message::with(['conversation'])
            ->latest()
            ->take(5)
            ->get(['id', 'conversation_id', 'content', 'sender_type', 'created_at']);

        $messageData = $messages->map(function ($msg) {
            return [
                'ID' => $msg->id,
                'Conv ID' => $msg->conversation_id,
                'Content' => substr($msg->content, 0, 50) . (strlen($msg->content) > 50 ? '...' : ''),
                'Sender' => $msg->sender_type,
                'Created' => $msg->created_at->format('H:i:s')
            ];
        });

        $this->table(['ID', 'Conv ID', 'Content', 'Sender', 'Created'], $messageData->toArray());

        // Test Configuration
        $this->info('⚙️  Configuration Check:');
        $config = [
            ['Setting', 'Value'],
            ['Broadcasting Driver', config('broadcasting.default')],
            ['Reverb Host', config('broadcasting.connections.reverb.options.host')],
            ['Reverb Port', config('broadcasting.connections.reverb.options.port')],
            ['App Environment', config('app.env')],
            ['Debug Mode', config('app.debug') ? 'ON' : 'OFF'],
        ];
        $this->table($config[0], array_slice($config, 1));

        $this->newLine();
        $this->info('✅ System test completed successfully!');
        $this->info('🚀 Your chatbot application is ready to use.');
        $this->newLine();
        $this->info('📱 Access URLs:');
        $this->info('   • Main App: http://127.0.0.1:8000');
        $this->info('   • Chat: http://127.0.0.1:8000/chat');
        $this->info('   • Agent Dashboard: http://127.0.0.1:8000/agent/dashboard');
        $this->info('   • WebSocket Test: http://127.0.0.1:8000/test-reverb.html');

        return 0;
    }
}
