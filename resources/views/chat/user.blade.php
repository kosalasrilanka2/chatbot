<x-user-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Customer Support Chat') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Chat Container -->
                <div class="p-6">
                    <!-- Chat Status Bar -->
                    <div id="chat-status" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
                            <span class="text-blue-700 font-medium">Welcome! Please select your preferred language and support area below.</span>
                        </div>
                    </div>

                    <!-- Skill Selection Panel -->
                    <div id="skill-selection" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Support Preferences</h3>
                        
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <!-- Language Selection -->
                            <div>
                                <label for="preferred-language" class="block text-sm font-medium text-gray-700 mb-2">
                                    Preferred Language <span class="text-red-500">*</span>
                                </label>
                                <select id="preferred-language" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Language</option>
                                    <option value="SI">සිංහල (Sinhala)</option>
                                    <option value="TI">தமிழ் (Tamil)</option>
                                    <option value="EN">English</option>
                                </select>
                            </div>

                            <!-- Domain Selection -->
                            <div>
                                <label for="preferred-domain" class="block text-sm font-medium text-gray-700 mb-2">
                                    Support Area <span class="text-red-500">*</span>
                                </label>
                                <select id="preferred-domain" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Support Area</option>
                                    <option value="FINANCE">💰 Finance</option>
                                    <option value="HR">👥 Human Resources</option>
                                    <option value="IT">💻 Information Technology</option>
                                    <option value="NETWORK">🌐 Network Support</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-sm text-gray-600">
                            <p>💡 Selecting your preferred language and support area helps us connect you with the most suitable agent.</p>
                        </div>
                    </div>

                    <!-- Messages Container -->
                    <div id="messages-container" class="h-96 overflow-y-auto mb-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <div class="text-center text-gray-500 py-8">
                            <div class="text-4xl mb-2">💬</div>
                            <p>Send a message to start chatting with our support team</p>
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="flex space-x-2">
                        <input type="text" 
                               id="message-input" 
                               placeholder="Type your message here..." 
                               class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <button onclick="sendMessage()" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentConversationId = null;
        let isConversationStarted = false;
        let channel = null;

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user has an active conversation
            checkExistingConversation();
            
            // Setup message input event
            document.getElementById('message-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });

        function checkExistingConversation() {
            fetch('/chat/conversations')
                .then(response => response.json())
                .then(conversations => {
                    if (conversations.length > 0) {
                        // User has existing conversations, use the most recent one
                        const latestConversation = conversations[0];
                        currentConversationId = latestConversation.id;
                        isConversationStarted = true;
                        
                        // Hide skill selection panel for existing conversations
                        document.getElementById('skill-selection').style.display = 'none';
                        
                        // Update status
                        updateChatStatus(latestConversation);
                        
                        // Load existing messages
                        loadMessages(currentConversationId);
                        
                        // Start listening to this conversation
                        listenToConversation(currentConversationId);
                    }
                })
                .catch(error => console.error('Error checking existing conversation:', error));
        }

        function sendMessage() {
            const input = document.getElementById('message-input');
            const content = input.value.trim();
            
            if (!content) return;

            // Check if skill preferences are selected for new conversations
            if (!isConversationStarted) {
                const language = document.getElementById('preferred-language').value;
                const domain = document.getElementById('preferred-domain').value;
                
                if (!language || !domain) {
                    alert('Please select your preferred language and support area before starting the conversation.');
                    return;
                }
            }

            if (!isConversationStarted) {
                // First message - create conversation with skill preferences
                createConversationAndSendMessage(content);
            } else {
                // Send message to existing conversation
                sendMessageToConversation(content);
            }
            
            input.value = '';
        }

        function createConversationAndSendMessage(content) {
            const language = document.getElementById('preferred-language').value;
            const domain = document.getElementById('preferred-domain').value;
            
            // Hide skill selection panel
            document.getElementById('skill-selection').style.display = 'none';
            
            // Update status to show we're connecting
            document.getElementById('chat-status').innerHTML = `
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse"></div>
                    <span class="text-yellow-700 font-medium">Connecting you with a ${getLanguageName(language)} speaking ${getDomainName(domain)} specialist...</span>
                </div>
            `;

            fetch('/chat/conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    title: `${getDomainName(domain)} Support Request`,
                    preferred_language: language,
                    preferred_domain: domain
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Conversation created:', data);
                
                currentConversationId = data.conversation.id;
                isConversationStarted = true;
                
                // Update status based on assignment
                updateChatStatus(data.conversation, data.auto_assigned, data.agent_name);
                
                // Clear the welcome message
                document.getElementById('messages-container').innerHTML = '';
                
                // Start listening to this conversation
                listenToConversation(currentConversationId);
                
                // Now send the first message
                sendMessageToConversation(content);
            })
            .catch(error => {
                console.error('Error creating conversation:', error);
                // Reset status on error and show skill selection again
                document.getElementById('skill-selection').style.display = 'block';
                document.getElementById('chat-status').innerHTML = `
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-red-700 font-medium">Connection failed. Please try again.</span>
                    </div>
                `;
            });
        }

        function sendMessageToConversation(content) {
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
            .then(response => response.json())
            .then(data => {
                console.log('Message sent:', data);
                // Message will appear via WebSocket
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            });
        }

        function loadMessages(conversationId) {
            fetch(`/chat/conversation/${conversationId}/messages`)
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
            
            const isUser = message.sender_type === 'user';
            const alignClass = isUser ? 'justify-end' : 'justify-start';
            const bgClass = isUser ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200';
            const senderName = message.sender_name || (isUser ? 'You' : 'Support Agent');
            
            messageDiv.className = `flex ${alignClass} mb-4`;
            messageDiv.innerHTML = `
                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${bgClass}">
                    <div class="text-xs ${isUser ? 'text-blue-100' : 'text-gray-500'} mb-1">${senderName}</div>
                    <div class="text-sm">${message.content}</div>
                    <div class="text-xs ${isUser ? 'text-blue-200' : 'text-gray-400'} mt-1">
                        ${new Date(message.created_at).toLocaleTimeString()}
                    </div>
                </div>
            `;
            
            container.appendChild(messageDiv);
            scrollToBottom();
        }

        function listenToConversation(conversationId) {
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo not available for conversation listening');
                return;
            }

            if (channel) {
                console.log('Leaving previous channel');
                window.Echo.leaveChannel(channel.name);
            }

            console.log('🔊 USER: Subscribing to channel:', `conversation.${conversationId}`);
            
            channel = window.Echo.private(`conversation.${conversationId}`);
            
            channel.subscribed(() => {
                console.log('✅ USER: Successfully subscribed to conversation channel', conversationId);
            });

            channel.listen('.message.new', (e) => {
                console.log('🔔 USER: New message received:', e);
                appendMessage(e.message);
            });

            channel.error((error) => {
                console.error('❌ USER: Channel error:', error);
            });
        }

        function updateChatStatus(conversation, autoAssigned = null, agentName = null) {
            const statusDiv = document.getElementById('chat-status');
            
            if (autoAssigned && agentName) {
                statusDiv.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-green-700 font-medium">Connected to ${agentName}</span>
                    </div>
                `;
            } else if (conversation.status === 'active' && conversation.agent) {
                statusDiv.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-green-700 font-medium">Connected to ${conversation.agent.name}</span>
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse"></div>
                        <span class="text-yellow-700 font-medium">Waiting for available support agent...</span>
                    </div>
                `;
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            container.scrollTop = container.scrollHeight;
        }

        // Helper functions for skill display names
        function getLanguageName(code) {
            const languages = {
                'SI': 'Sinhala',
                'TI': 'Tamil',
                'EN': 'English'
            };
            return languages[code] || code;
        }

        function getDomainName(code) {
            const domains = {
                'FINANCE': 'Finance',
                'HR': 'HR',
                'IT': 'IT',
                'NETWORK': 'Network'
            };
            return domains[code] || code;
        }
    </script>
    @endpush
</x-user-layout>
