<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div id="chat-container" class="flex h-96">
                        <!-- Conversation List -->
                        <div class="w-1/3 border-r pr-4">
                            <div class="mb-4">
                                <button onclick="createNewConversation()" 
                                        class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    New Conversation
                                </button>
                            </div>
                            <div id="conversation-list" class="space-y-2">
                                <!-- Conversations will be loaded here -->
                            </div>
                        </div>

                        <!-- Chat Area -->
                        <div class="w-2/3 pl-4">
                            <div id="chat-area" class="h-full flex flex-col">
                                <!-- Chat Header -->
                                <div id="chat-header" class="border-b pb-2 mb-4">
                                    <p class="text-gray-500">Select a conversation to start chatting</p>
                                </div>

                                <!-- Messages Container -->
                                <div id="messages-container" class="flex-1 overflow-y-auto border border-gray-200 rounded p-4 mb-4">
                                    <!-- Messages will appear here -->
                                </div>

                                <!-- Message Input -->
                                <div id="message-input-container" class="hidden">
                                    <div class="flex space-x-2">
                                        <input type="text" 
                                               id="message-input" 
                                               placeholder="Type your message..." 
                                               class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
        let currentConversationId = null;
        const urlParams = new URLSearchParams(window.location.search);
        const conversationFromUrl = urlParams.get('conversation');

        // Listen for new messages
        function listenToConversation(conversationId) {
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo not available for conversation listening');
                return;
            }

            if (currentConversationId) {
                console.log('Leaving previous channel:', `private-conversation.${currentConversationId}`);
                window.Echo.leaveChannel(`private-conversation.${currentConversationId}`);
            }

            console.log('🔊 USER: Subscribing to channel:', `conversation.${conversationId}`);
            console.log('🔊 USER: Current user ID:', {{ auth()->user()->id }});
            
            const channel = window.Echo.private(`conversation.${conversationId}`);
            
            channel.subscribed(() => {
                console.log('✅ USER: Successfully subscribed to conversation channel', conversationId);
            });
            
            channel.error((error) => {
                console.error('❌ USER: Error subscribing to conversation channel:', error);
                console.error('❌ USER: Channel name was:', `conversation.${conversationId}`);
                console.error('❌ USER: User ID:', {{ auth()->user()->id }});
            });
            
            channel.listen('.message.new', (e) => {
                console.log('🔔 USER: New message received:', e);
                console.log('🔔 USER: Current conversation ID:', currentConversationId);
                console.log('🔔 USER: Message conversation ID:', e.message?.conversation_id);
                
                if (currentConversationId == conversationId) {
                    appendMessage(e.message);
                    scrollToBottom();
                } else {
                    console.log('🔔 USER: Message for different conversation, ignoring');
                }
                loadUserConversations();
            });

            currentConversationId = conversationId;
        }

        function createNewConversation() {
            fetch('/chat/conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    title: 'New Conversation'
                })
            })
            .then(response => response.json())
            .then(conversation => {
                loadUserConversations();
                selectConversation(conversation.id);
            })
            .catch(error => console.error('Error creating conversation:', error));
        }

        function loadUserConversations() {
            // Load conversations for the current user
            fetch('/chat/conversations')
                .then(response => response.json())
                .then(conversations => {
                    const container = document.getElementById('conversation-list');
                    container.innerHTML = '';
                    
                    conversations.forEach(conversation => {
                        const div = document.createElement('div');
                        div.className = `p-3 border rounded cursor-pointer hover:bg-gray-100 ${currentConversationId == conversation.id ? 'bg-blue-100' : ''}`;
                        div.onclick = () => selectConversation(conversation.id);
                        div.innerHTML = `
                            <h4 class="font-medium text-sm">${conversation.title || 'Conversation #' + conversation.id}</h4>
                            <p class="text-xs text-gray-600">Status: ${conversation.status}</p>
                            ${conversation.agent ? `<p class="text-xs text-gray-500">Agent: ${conversation.agent.name}</p>` : '<p class="text-xs text-gray-500">No agent assigned</p>'}
                            <p class="text-xs text-gray-500">${new Date(conversation.last_activity).toLocaleString()}</p>
                        `;
                        container.appendChild(div);
                    });
                })
                .catch(error => console.error('Error loading conversations:', error));
        }

        function selectConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('conversation', conversationId);
            window.history.pushState({}, '', url);
            
            // Update header
            document.getElementById('chat-header').innerHTML = `
                <p class="font-medium">Conversation #${conversationId}</p>
            `;
            
            // Show message input
            document.getElementById('message-input-container').classList.remove('hidden');
            
            // Load messages
            loadMessages(conversationId);
            
            // Listen to this conversation
            listenToConversation(conversationId);
            
            // Update conversation list styling
            loadUserConversations();
        }

        function loadMessages(conversationId) {
            fetch(`/chat/conversation/${conversationId}/messages`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text(); // Get as text first to see what we're receiving
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const messages = JSON.parse(text);
                        const container = document.getElementById('messages-container');
                        container.innerHTML = '';
                        
                        messages.forEach(message => {
                            appendMessage(message);
                        });
                        
                        scrollToBottom();
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        console.error('Response text was:', text);
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function appendMessage(message) {
            const container = document.getElementById('messages-container');
            const div = document.createElement('div');
            div.className = `mb-3 ${message.sender_type === 'user' ? 'text-right' : 'text-left'}`;
            
            const isCurrentUser = message.sender_type === 'user' && {{ auth()->user()->id }} == message.sender_id;
            const bgColor = isCurrentUser ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800';
            
            // Use sender_name if available, otherwise fallback to defaults
            const senderName = message.sender_name || 
                              (message.sender_type === 'user' ? '{{ auth()->user()->name }}' : 'Agent');
            
            div.innerHTML = `
                <div class="inline-block max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${bgColor}">
                    <p class="text-sm">${message.content}</p>
                    <p class="text-xs mt-1 opacity-75">
                        ${senderName} • ${new Date(message.created_at).toLocaleTimeString()}
                    </p>
                </div>
            `;
            container.appendChild(div);
        }

        function sendMessage() {
            const input = document.getElementById('message-input');
            const content = input.value.trim();
            
            if (!content || !currentConversationId) {
                console.log('Cannot send message:', { content: !!content, conversationId: currentConversationId });
                return;
            }
            
            console.log('Sending message:', { content, conversationId: currentConversationId });
            
            fetch('/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    content: content,
                    sender_type: 'user'
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(response => {
                console.log('Message sent successfully:', response);
                input.value = '';
                
                // Message will appear via real-time WebSocket subscription
                // No need to add immediately since real-time is working
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message. Check console for details.');
            });
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            container.scrollTop = container.scrollHeight;
        }

        // Handle Enter key in message input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('message-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Load conversations
            loadUserConversations();
            
            // If conversation ID in URL, select it
            if (conversationFromUrl) {
                selectConversation(conversationFromUrl);
            }
        });
    </script>
    @endpush
</x-app-layout>
