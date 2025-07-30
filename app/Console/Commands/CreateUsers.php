<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test users and agents with proper authentication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Creating users and agents...');

        // Delete existing data in order (respecting foreign key constraints)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Message::truncate();
        Conversation::truncate();
        Agent::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Create regular users
        $user1 = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        $this->info("✅ Created user: {$user1->email} (password: password)");

        $user2 = User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        $this->info("✅ Created user: {$user2->email} (password: password)");

        // Create agent users (they need both User and Agent records)
        $agentUser1 = User::create([
            'name' => 'Support Agent',
            'email' => 'agent@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        // Create corresponding Agent record
        $agent1 = Agent::create([
            'name' => 'Support Agent',
            'email' => 'agent@test.com',
            'status' => 'online',
            'last_seen' => now(),
        ]);
        
        $this->info("✅ Created agent user: {$agentUser1->email} (password: password)");

        $agentUser2 = User::create([
            'name' => 'Senior Agent',
            'email' => 'senior@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        // Create corresponding Agent record
        $agent2 = Agent::create([
            'name' => 'Senior Agent',
            'email' => 'senior@test.com',
            'status' => 'online',
            'last_seen' => now(),
        ]);
        
        $this->info("✅ Created agent user: {$agentUser2->email} (password: password)");

        $this->info('');
        $this->info('🎉 All users and agents created successfully!');
        $this->info('');
        $this->info('👤 Regular Users (access /chat after login):');
        $this->info('  • user@test.com (password: password)');
        $this->info('  • john@test.com (password: password)');
        $this->info('');
        $this->info('🎧 Agent Users (access /agent/dashboard after login):');
        $this->info('  • agent@test.com (password: password)');
        $this->info('  • senior@test.com (password: password)');
        $this->info('');
        $this->info('🔗 Login URL: http://localhost:8000/login');

        return 0;
    }
}
