import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    enableLogging: true,
    logToConsole: true,
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
    authEndpoint: '/broadcasting/auth',
});

// Add connection event listeners for debugging
window.Echo.connector.pusher.connection.bind('connected', function() {
    console.log('✅ Echo WebSocket connected successfully!');
});

window.Echo.connector.pusher.connection.bind('disconnected', function() {
    console.log('❌ Echo WebSocket disconnected');
});

window.Echo.connector.pusher.connection.bind('error', function(err) {
    console.error('❌ Echo WebSocket connection error:', err);
});

window.Echo.connector.pusher.connection.bind('connecting', function() {
    console.log('🔄 Echo WebSocket connecting...');
});

window.Echo.connector.pusher.connection.bind('unavailable', function() {
    console.error('❌ Echo WebSocket unavailable');
});

console.log('📡 Echo configuration:', {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME
});
