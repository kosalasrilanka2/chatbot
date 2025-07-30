<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Echo Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🔍 Echo Connection Test</h1>
    <div id="logs"></div>
    
    <div style="margin: 20px 0;">
        <button onclick="testBroadcast()">📡 Test Broadcast</button>
        <button onclick="clearLogs()">🧹 Clear Logs</button>
    </div>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        function log(message, type = 'info') {
            const logs = document.getElementById('logs');
            const div = document.createElement('div');
            div.className = `log ${type}`;
            div.innerHTML = `<strong>${new Date().toLocaleTimeString()}</strong> - ${message}`;
            logs.appendChild(div);
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function clearLogs() {
            document.getElementById('logs').innerHTML = '';
        }

        // Wait for Echo to be available
        function initializeEchoTest() {
            if (typeof window.Echo === 'undefined') {
                log('❌ Echo is not available - waiting...', 'error');
                setTimeout(initializeEchoTest, 1000);
                return;
            }

            log('✅ Echo is available!', 'success');
            log(`📍 Echo connector: ${window.Echo.connector}`, 'info');

            // Test connection to a simple channel
            try {
                log('🔌 Attempting to connect to test channel...', 'info');
                
                const testChannel = window.Echo.private('conversation.1');
                
                testChannel.subscribed(() => {
                    log('✅ Successfully subscribed to conversation.1 channel!', 'success');
                });

                testChannel.error((error) => {
                    log('❌ Error subscribing to conversation.1: ' + JSON.stringify(error), 'error');
                });

                testChannel.listen('.message.new', (e) => {
                    log('🔔 Received message.new event: ' + JSON.stringify(e), 'success');
                });

                // Test agent channel if logged in as agent
                @auth
                    @php
                        $agent = \App\Models\Agent::where('email', auth()->user()->email)->first();
                    @endphp
                    @if($agent)
                        log('🤖 Setting up agent channel for agent {{ $agent->id }}', 'info');
                        const agentChannel = window.Echo.private('agent.{{ $agent->id }}');
                        
                        agentChannel.subscribed(() => {
                            log('✅ Successfully subscribed to agent channel!', 'success');
                        });

                        agentChannel.error((error) => {
                            log('❌ Error subscribing to agent channel: ' + JSON.stringify(error), 'error');
                        });

                        agentChannel.listen('.message.new', (e) => {
                            log('🔔 Received agent message: ' + JSON.stringify(e), 'success');
                        });
                    @endif
                @endauth
                
            } catch (error) {
                log('❌ Exception while setting up Echo: ' + error.message, 'error');
            }
        }

        function testBroadcast() {
            log('📡 Triggering test broadcast...', 'info');
            fetch('/chat/debug-broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                log('📡 Broadcast response: ' + JSON.stringify(data), 'info');
            })
            .catch(error => {
                log('❌ Broadcast failed: ' + error.message, 'error');
            });
        }

        // Start the test
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Starting Echo connection test...', 'info');
            setTimeout(initializeEchoTest, 1000);
        });
    </script>
</body>
</html>
