<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Echo Test') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Echo WebSocket Test</h3>
                    
                    <div id="echo-status" class="mb-4 p-4 border rounded">
                        <p>Checking Echo status...</p>
                    </div>
                    
                    <div id="test-results" class="space-y-2">
                        <!-- Test results will appear here -->
                    </div>
                    
                    <button onclick="testBroadcast()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Test Broadcast
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function addResult(message, isError = false) {
            const div = document.createElement('div');
            div.className = `p-2 rounded ${isError ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`;
            div.textContent = new Date().toLocaleTimeString() + ': ' + message;
            document.getElementById('test-results').appendChild(div);
        }

        function updateStatus(message, isError = false) {
            const statusDiv = document.getElementById('echo-status');
            statusDiv.className = `mb-4 p-4 border rounded ${isError ? 'bg-red-100 border-red-300' : 'bg-green-100 border-green-300'}`;
            statusDiv.innerHTML = `<p>${message}</p>`;
        }

        function testEcho() {
            if (typeof window.Echo === 'undefined') {
                updateStatus('❌ Echo is not available', true);
                addResult('Echo not loaded', true);
                return false;
            }

            updateStatus('✅ Echo is available');
            addResult('Echo loaded successfully');

            try {
                // Test public channel
                const channel = window.Echo.channel('test-echo-channel');
                addResult('Public channel connection attempted');

                channel.listen('.test-event', function(e) {
                    addResult('Received test event: ' + JSON.stringify(e));
                });

                return true;
            } catch (error) {
                addResult('Error setting up Echo: ' + error.message, true);
                return false;
            }
        }

        function testBroadcast() {
            fetch('/broadcast:test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                addResult('Broadcast test triggered: ' + JSON.stringify(data));
            })
            .catch(error => {
                addResult('Broadcast test failed: ' + error.message, true);
            });
        }

        // Wait for page to load
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for Echo to initialize
            setTimeout(function() {
                testEcho();
            }, 1000);
        });
    </script>
    @endpush
</x-app-layout>
