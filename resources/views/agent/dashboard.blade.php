<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Agent Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column: Agent Controls & Conversations -->
                        <div>
                            <!-- Agent Status Controls -->
                            <div class="mb-6">
                                <h3 class="text-lg font-medium mb-4">Agent Status</h3>
                                <div class="flex space-x-4">
                                    <button onclick="updateStatus('online')" 
                                            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                        Online
                                    </button>
                                    <button onclick="updateStatus('busy')" 
                                            class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                                        Busy
                                    </button>
                                    <button onclick="updateStatus('offline')" 
                                            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                                        Offline
                                    </button>
                                </div>
                                <div id="current-status" class="mt-2 text-sm text-gray-600"></div>
                            </div>

                            <!-- Unread Messages Counter -->
                            <div class="mb-6">
                                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm">
                                                You have <span id="unread-count" class="font-bold">0</span> unread messages
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conversations List -->
                            <div>
                                <h3 class="text-lg font-medium mb-4">Active Conversations</h3>
                                <div id="conversations-list" class="space-y-4">
                                    <!-- Conversations will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Conversation View -->
                        <div id="conversation-view" class="hidden">
                            <div class="bg-gray-50 rounded-lg border">
                                <div class="p-6 border-b bg-white rounded-t-lg">
                                    <div class="flex justify-between items-center">
                                        <h3 id="conversation-title" class="text-lg font-medium">Conversation</h3>
                                        <button onclick="closeConversation()" class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Messages Container -->
                                <div id="messages-container" class="h-96 overflow-y-auto p-6 space-y-4 bg-white">
                                    <!-- Messages will be loaded here -->
                                </div>
                                
                                <!-- Message Input -->
                                <div class="p-6 border-t bg-white rounded-b-lg">
                                    <div class="flex space-x-2">
                                        <input type="text" 
                                               id="message-input" 
                                               placeholder="Type your response..." 
                                               class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               onkeypress="if(event.key === 'Enter') sendMessage()">
                                        <button onclick="sendMessage()" 
                                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            Send
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Wait for DOM and Echo to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Function to initialize Echo connections
            function initializeEcho() {
                if (typeof window.Echo === 'undefined') {
                    console.warn('Echo not ready yet, retrying in 500ms...');
                    setTimeout(initializeEcho, 500);
                    return;
                }

                console.log('Echo initialized, setting up connections...');

                // Initialize Echo for real-time updates
                console.log('Setting up agent channels...');
                
                const agentsChannel = window.Echo.channel('agents');
                agentsChannel.subscribed(() => {
                    console.log('✅ Subscribed to agents channel');
                });
                agentsChannel.listen('.agent.status.updated', (e) => {
                    console.log('Agent status updated:', e);
                });

                // Listen for new messages on agent channel
                @auth
                    @php
                        $agent = \App\Models\Agent::where('email', auth()->user()->email)->first();
                    @endphp
                    @if($agent)
                        console.log('Setting up agent private channel for agent ID: {{ $agent->id }}');
                        const agentChannel = window.Echo.private('agent.{{ $agent->id }}');
                        
                        agentChannel.subscribed(() => {
                            console.log('✅ Subscribed to agent private channel');
                        });
                        
                        agentChannel.error((error) => {
                            console.error('❌ Error subscribing to agent channel:', error);
                        });
                        
                        agentChannel.listen('.message.new', (e) => {
                            console.log('🔔 New message for agent:', e);
                            updateUnreadCount();
                            loadConversations();
                            
                            // Show notification
                            showNotification('New message received!');
                        });
                    @else
                        console.warn('⚠️ No agent found for current user');
                    @endif
                @endauth
            }

            // Start initialization
            initializeEcho();

            // Load initial data
            loadConversations();
            updateUnreadCount();
        });

        function updateStatus(status) {
            fetch('/agent/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('current-status').textContent = `Current Status: ${status}`;
                showNotification(`Status updated to ${status}`);
            })
            .catch(error => console.error('Error updating status:', error));
        }

        function loadConversations() {
            fetch('/agent/conversations')
                .then(response => response.json())
                .then(conversations => {
                    const container = document.getElementById('conversations-list');
                    container.innerHTML = '';
                    
                    conversations.forEach(conversation => {
                        const div = document.createElement('div');
                        div.className = 'border rounded p-4 hover:bg-gray-50 cursor-pointer';
                        div.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div onclick="openConversation(${conversation.id})">
                                    <h4 class="font-medium">${conversation.title || 'Conversation #' + conversation.id}</h4>
                                    <p class="text-sm text-gray-600">User: ${conversation.user.name}</p>
                                    <p class="text-sm text-gray-500">Status: ${conversation.status}</p>
                                    <p class="text-sm text-gray-500">Last Activity: ${new Date(conversation.last_activity).toLocaleString()}</p>
                                </div>
                                <div class="flex space-x-2">
                                    ${!conversation.agent_id ? `
                                        <button onclick="assignConversation(${conversation.id})" 
                                                class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600">
                                            Assign
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                })
                .catch(error => console.error('Error loading conversations:', error));
        }

        let currentConversationId = null;
        let conversationChannel = null;

        function openConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Show conversation view
            document.getElementById('conversation-view').classList.remove('hidden');
            
            // Load conversation details
            fetch(`/agent/conversation/${conversationId}`)
                .then(response => response.json())
                .then(conversation => {
                    document.getElementById('conversation-title').textContent = 
                        conversation.title || `Conversation with ${conversation.user.name}`;
                })
                .catch(error => console.error('Error loading conversation:', error));
            
            // Load messages
            loadMessages(conversationId);
            
            // Subscribe to conversation channel for real-time updates
            subscribeToConversation(conversationId);
        }

        function closeConversation() {
            document.getElementById('conversation-view').classList.add('hidden');
            
            // Leave conversation channel
            if (conversationChannel) {
                window.Echo.leaveChannel(`private-conversation.${currentConversationId}`);
                conversationChannel = null;
            }
            
            currentConversationId = null;
        }

        function loadMessages(conversationId) {
            fetch(`/agent/conversation/${conversationId}/messages`)
                .then(response => response.json())
                .then(messages => {
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '';
                    
                    messages.forEach(message => {
                        appendMessage(message);
                    });
                    
                    scrollToBottom();
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function appendMessage(message) {
            const container = document.getElementById('messages-container');
            const messageDiv = document.createElement('div');
            
            const isAgent = message.sender_type === 'agent';
            messageDiv.className = `flex ${isAgent ? 'justify-end' : 'justify-start'} mb-4`;
            
            messageDiv.innerHTML = `
                <div class="${isAgent ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-900'} rounded-lg px-4 py-2 max-w-xs lg:max-w-md">
                    <div class="text-sm font-medium mb-1">${message.sender_name}</div>
                    <div>${message.content}</div>
                    <div class="text-xs ${isAgent ? 'text-blue-100' : 'text-gray-500'} mt-1">
                        ${new Date(message.created_at).toLocaleTimeString()}
                    </div>
                </div>
            `;
            
            container.appendChild(messageDiv);
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            container.scrollTop = container.scrollHeight;
        }

        function subscribeToConversation(conversationId) {
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo not available for conversation subscription');
                return;
            }

            // Leave previous channel if exists
            if (conversationChannel) {
                console.log('🔊 AGENT: Leaving previous conversation channel');
                window.Echo.leaveChannel(`private-conversation.${currentConversationId}`);
            }

            console.log('🔊 AGENT: Subscribing to conversation channel:', `conversation.${conversationId}`);
            @auth
                @php
                    $agent = \App\Models\Agent::where('email', auth()->user()->email)->first();
                @endphp
                @if($agent)
                    console.log('🔊 AGENT: Current agent ID:', {{ $agent->id }});
                    console.log('🔊 AGENT: Current user ID:', {{ auth()->user()->id }});
                @endif
            @endauth
            
            conversationChannel = window.Echo.private(`conversation.${conversationId}`);
            
            conversationChannel.subscribed(() => {
                console.log('✅ AGENT: Successfully subscribed to conversation channel', conversationId);
            });
            
            conversationChannel.error((error) => {
                console.error('❌ AGENT: Error subscribing to conversation:', error);
                console.error('❌ AGENT: Channel name was:', `conversation.${conversationId}`);
                @auth
                    @php
                        $agent = \App\Models\Agent::where('email', auth()->user()->email)->first();
                    @endphp
                    @if($agent)
                        console.error('❌ AGENT: Agent ID:', {{ $agent->id }});
                        console.error('❌ AGENT: User ID:', {{ auth()->user()->id }});
                    @endif
                @endauth
            });
            
            conversationChannel.listen('.message.new', (e) => {
                console.log('🔔 AGENT: Received conversation message:', e);
                console.log('🔔 AGENT: Current conversation ID:', currentConversationId);
                console.log('🔔 AGENT: Message conversation ID:', e.message?.conversation_id);
                
                if (currentConversationId == conversationId) {
                    appendMessage(e.message);
                    scrollToBottom();
                } else {
                    console.log('🔔 AGENT: Message for different conversation, ignoring');
                }
                updateUnreadCount();
                loadConversations();
            });
        }

        function sendMessage() {
            if (!currentConversationId) {
                console.error('No conversation selected');
                return;
            }

            const input = document.getElementById('message-input');
            const content = input.value.trim();
            
            if (!content) {
                return;
            }

            fetch('/agent/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message === 'Message sent successfully') {
                    input.value = '';
                    
                    // Message will appear via real-time WebSocket subscription
                    // No need to add immediately since real-time is working
                } else {
                    console.error('Error sending message:', data);
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }

        function assignConversation(conversationId) {
            fetch(`/agent/conversation/${conversationId}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                showNotification('Conversation assigned successfully');
                loadConversations();
            })
            .catch(error => console.error('Error assigning conversation:', error));
        }

        function updateUnreadCount() {
            fetch('/agent/unread-count')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('unread-count').textContent = data.unread_count;
                })
                .catch(error => console.error('Error updating unread count:', error));
        }

        function showNotification(message) {
            // Simple notification - you can replace with a better notification library
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 3000);
        }
    </script>
    @endpush
</x-app-layout>
