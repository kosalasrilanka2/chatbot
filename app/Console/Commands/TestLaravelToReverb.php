<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestLaravelToReverb extends Command
{
    protected $signature = 'reverb:test-laravel-connection';
    protected $description = 'Test Laravel to Reverb HTTP connection';

    public function handle()
    {
        $this->info('🔌 Testing Laravel → Reverb Connection...');

        $config = config('broadcasting.connections.reverb');
        $this->info('Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['App ID', $config['app_id']],
                ['Key', $config['key']],
                ['Secret', substr($config['secret'], 0, 8) . '...'],
                ['Host', $config['options']['host']],
                ['Port', $config['options']['port']],
                ['Scheme', $config['options']['scheme']],
                ['useTLS', $config['options']['useTLS'] ? 'true' : 'false'],
            ]
        );

        $url = sprintf(
            '%s://%s:%s/apps/%s/events',
            $config['options']['scheme'],
            $config['options']['host'],
            $config['options']['port'],
            $config['app_id']
        );

        $this->info("🎯 Target URL: {$url}");

        try {
            $this->info('📡 Sending test HTTP request to Reverb...');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['key'],
                'Content-Type' => 'application/json',
            ])->post($url, [
                'name' => 'test-event',
                'channel' => 'test-channel',
                'data' => json_encode(['message' => 'Test from Laravel']),
            ]);

            $this->info("📥 Response Status: {$response->status()}");
            $this->info("📥 Response Body: {$response->body()}");

            if ($response->successful()) {
                $this->info('✅ HTTP connection to Reverb successful!');
            } else {
                $this->error('❌ HTTP connection failed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
        }

        return 0;
    }
}
