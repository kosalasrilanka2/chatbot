<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        
        <!-- Debug Echo loading -->
        <script>
            console.log('DOM loaded, checking Echo...');
            
            function checkEcho() {
                if (typeof window.Echo !== 'undefined') {
                    console.log('✅ Echo is available:', window.Echo);
                } else {
                    console.log('❌ Echo is not available');
                }
                
                if (typeof window.Pusher !== 'undefined') {
                    console.log('✅ Pusher is available:', window.Pusher);
                } else {
                    console.log('❌ Pusher is not available');
                }
            }
            
            // Check immediately
            checkEcho();
            
            // Check after a delay
            setTimeout(checkEcho, 1000);
            
            // Check when page is fully loaded
            window.addEventListener('load', checkEcho);
        </script>
        
        @stack('scripts')
    </body>
</html>
