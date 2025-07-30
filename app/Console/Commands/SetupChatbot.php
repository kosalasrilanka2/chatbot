<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupChatbot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:setup {--fresh : Fresh setup (clears all data)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete setup of chatbot application with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up Laravel Chatbot Application...');
        $this->info('');

        if ($this->option('fresh')) {
            $this->info('🔄 Running fresh setup...');

            // Fresh migrations
            $this->call('migrate:fresh');
        } else {
            // Regular migrations
            $this->call('migrate');
        }

        // Create users and agents
        $this->info('');
        $this->call('users:create');

        // Create sample data
        $this->info('');
        $this->call('data:sample');

        $this->info('');
        $this->info('🎉 Chatbot setup completed successfully!');
        $this->info('');
        $this->info('🌐 Application URLs:');
        $this->info('  • Main Site: http://localhost:8000');
        $this->info('  • Login: http://localhost:8000/login');
        $this->info('  • User Chat: http://localhost:8000/chat');
        $this->info('  • Agent Dashboard: http://localhost:8000/agent/dashboard');
        $this->info('  • Admin Dashboard: http://localhost:8000/admin/dashboard (no auth required)');
        $this->info('');
        $this->info('👤 Test Accounts:');
        $this->info('  Regular Users:');
        $this->info('    • user@test.com / password');
        $this->info('    • john@test.com / password');
        $this->info('  Agents:');
        $this->info('    • agent@test.com / password');
        $this->info('    • senior@test.com / password');
        $this->info('');
        $this->info('⚡ To start the servers:');
        $this->info('  Terminal 1: php artisan serve');
        $this->info('  Terminal 2: php artisan reverb:start');
        $this->info('  Terminal 3: npm run dev');
        $this->info('');
        $this->info('🛠️ Useful commands:');
        $this->info('  • php artisan conversations:clear - Clear all conversations/messages');
        $this->info('  • php artisan conversations:process-waiting - Process waiting conversations');
        $this->info('  • php artisan data:sample - Create sample conversations');

        return 0;
    }
}
