<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create agent user
        $agentUser = User::create([
            'name' => 'Agent Smith',
            'email' => 'agent@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create agent profile
        Agent::create([
            'name' => 'Agent Smith',
            'email' => 'agent@example.com',
            'status' => 'offline',
            'last_seen' => now(),
        ]);

        // Create another agent
        $agentUser2 = User::create([
            'name' => 'Agent Johnson',
            'email' => 'johnson@example.com',
            'password' => Hash::make('password'),
        ]);

        Agent::create([
            'name' => 'Agent Johnson',
            'email' => 'johnson@example.com',
            'status' => 'offline',
            'last_seen' => now(),
        ]);

        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
