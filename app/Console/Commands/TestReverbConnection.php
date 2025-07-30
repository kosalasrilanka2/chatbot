<?php

namespace App\Console\Commands;

use App\Events\NewMessageEvent;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Console\Command;

class TestReverbConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Reverb WebSocket connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Reverb WebSocket connection...');
        
        try {
            $this->info('Testing Reverb connection by checking configuration...');
            
            // Check broadcasting driver
            $driver = config('broadcasting.default');
            $this->info("Broadcasting driver: {$driver}");
            
            if ($driver !== 'reverb') {
                $this->warn('⚠️  Broadcasting driver is not set to "reverb"');
                $this->info('Please set BROADCAST_CONNECTION=reverb in your .env file');
            } else {
                $this->info('✅ Broadcasting driver is correctly set to "reverb"');
            }
            
            // Check Reverb configuration
            $reverbConfig = config('broadcasting.connections.reverb');
            $this->info('Reverb Configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['App ID', $reverbConfig['app_id'] ?? 'Not set'],
                    ['App Key', $reverbConfig['key'] ?? 'Not set'],
                    ['Host', $reverbConfig['options']['host'] ?? 'Not set'],
                    ['Port', $reverbConfig['options']['port'] ?? 'Not set'],
                    ['Scheme', $reverbConfig['options']['scheme'] ?? 'Not set'],
                ]
            );
            
            $this->newLine();
            $this->info('✅ Reverb configuration loaded successfully!');
            $this->info('🔗 You can test the connection by opening: http://127.0.0.1:8000/test-reverb.html');
            
            $this->newLine();
            $this->info('If Reverb is working correctly, you should see this event in:');
            $this->info('- Browser console if connected to test-channel');
            $this->info('- Reverb server logs');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to broadcast test message: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
