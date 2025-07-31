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
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
                                <span class="text-blue-700 font-medium">Welcome! Please select your preferred language and support area below.</span>
                            </div>
                            <!-- End Conversation Button (hidden initially) -->
                            <button id="end-conversation-btn" 
                                    onclick="endCurrentConversation()" 
                                    class="hidden px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                End Conversation
                            </button>
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
        // Global variables
        const currentUserId = {{ auth()->id() ?? 'null' }};
        let currentConversationId = null;
        let isConversationStarted = false;
        let channel = null;
        let userChannel = null;
        let transferNotifications = new Set(); // Track transfer notifications for debugging
        
        console.log('🔍 Current user ID:', currentUserId);

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Subscribe to user channel for transfer notifications
            if (currentUserId && typeof window.Echo !== 'undefined') {
                console.log('🔊 USER: Subscribing to user channel:', `user.${currentUserId}`);
                userChannel = window.Echo.private(`user.${currentUserId}`);
                
                userChannel.subscribed(() => {
                    console.log('✅ USER: Successfully subscribed to user channel');
                });
                
                userChannel.listen('.agent-transfer', (e) => {
                    console.log('🔄 USER: Transfer notification from user channel:', e);
                    handleAgentTransfer(e);
                });
                
                userChannel.error((error) => {
                    console.error('❌ USER: User channel error:', error);
                });
            }
            
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
                
                // If this is a system message from a new agent joining, it might indicate a successful transfer
                if (e.message.sender_type === 'system' && e.message.content.includes("I'm") && e.message.content.includes("continuing to assist")) {
                    console.log('🔄 Detected agent assignment through system message');
                    // This indicates an agent has been assigned, let's check for existing transfer notifications
                    setTimeout(() => {
                        const existingWaitingNotifications = document.querySelectorAll('[data-transfer-notification] .bg-yellow-50');
                        if (existingWaitingNotifications.length > 0) {
                            console.log('🗑️ Removing waiting notifications due to agent assignment');
                            existingWaitingNotifications.forEach(notification => {
                                const parentDiv = notification.closest('[data-transfer-notification]');
                                if (parentDiv) parentDiv.remove();
                            });
                        }
                    }, 100);
                }
            });

            channel.listen('.agent-transfer', (e) => {
                console.log('🔄 USER: Agent transfer notification:', e);
                handleAgentTransfer(e);
            });

            channel.error((error) => {
                console.error('❌ USER: Channel error:', error);
            });
        }

        function updateChatStatus(conversation, autoAssigned = null, agentName = null) {
            const statusDiv = document.getElementById('chat-status');
            
            if (autoAssigned && agentName) {
                statusDiv.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-green-700 font-medium">Connected to ${agentName}</span>
                        </div>
                        <button id="end-conversation-btn" 
                                onclick="endCurrentConversation()" 
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            End Conversation
                        </button>
                    </div>
                `;
            } else if (conversation.status === 'active' && conversation.agent) {
                statusDiv.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-green-700 font-medium">Connected to ${conversation.agent.name}</span>
                        </div>
                        <button id="end-conversation-btn" 
                                onclick="endCurrentConversation()" 
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            End Conversation
                        </button>
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse"></div>
                            <span class="text-yellow-700 font-medium">Waiting for available support agent...</span>
                        </div>
                        <button id="end-conversation-btn" 
                                onclick="endCurrentConversation()" 
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            End Conversation
                        </button>
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

        // End current conversation and start fresh
        function endCurrentConversation() {
            if (!confirm('Are you sure you want to end this conversation? This action cannot be undone.')) {
                return;
            }

            if (currentConversationId) {
                // Send request to close conversation
                fetch(`/chat/conversation/${currentConversationId}/close`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Conversation closed:', data);
                })
                .catch(error => {
                    console.error('Error closing conversation:', error);
                });

                // Leave the WebSocket channel
                if (channel) {
                    window.Echo.leaveChannel(channel.name);
                    channel = null;
                }
            }

            // Reset the interface
            resetConversationInterface();
        }

        function resetConversationInterface() {
            // Reset variables
            currentConversationId = null;
            isConversationStarted = false;

            // Clear messages
            document.getElementById('messages-container').innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-2">💬</div>
                    <p>Send a message to start chatting with our support team</p>
                </div>
            `;

            // Reset status bar
            document.getElementById('chat-status').innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
                        <span class="text-blue-700 font-medium">Welcome! Please select your preferred language and support area below.</span>
                    </div>
                    <button id="end-conversation-btn" 
                            onclick="endCurrentConversation()" 
                            class="hidden px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        End Conversation
                    </button>
                </div>
            `;

            // Show skill selection panel
            document.getElementById('skill-selection').style.display = 'block';

            // Reset skill selections
            document.getElementById('preferred-language').value = '';
            document.getElementById('preferred-domain').value = '';

            // Clear message input
            document.getElementById('message-input').value = '';
        }

        function handleAgentTransfer(transferData) {
            console.log('🔄 Handling agent transfer:', transferData);
            console.log('🔍 Transfer data details:', {
                new_agent: transferData.new_agent,
                old_agent: transferData.old_agent,
                transfer_reason: transferData.transfer_reason,
                conversation_id: transferData.conversation_id
            });
            
            const messagesContainer = document.getElementById('messages-container');
            
            // Remove any existing transfer notifications
            const existingTransferNotifications = messagesContainer.querySelectorAll('[data-transfer-notification]');
            console.log('🗑️ Found existing transfer notifications:', existingTransferNotifications.length);
            existingTransferNotifications.forEach(notification => {
                console.log('🗑️ Removing transfer notification:', notification);
                notification.remove();
            });
            
            // Create transfer notification element
            const transferNotification = document.createElement('div');
            transferNotification.className = 'flex justify-center my-4';
            transferNotification.setAttribute('data-transfer-notification', 'true');
            
            let notificationContent = '';
            
            if (transferData.new_agent) {
                console.log('✅ New agent assigned:', transferData.new_agent.name);
                // Agent successfully transferred/assigned
                const isNewAssignment = transferData.transfer_reason === 'agent_assigned';
                const headerText = isNewAssignment ? 'Connected!' : 'Agent Transfer';
                const messageText = isNewAssignment ? 
                    `<strong>${transferData.new_agent.name}</strong> is now attending to your conversation` :
                    `You've been connected to <strong>${transferData.new_agent.name}</strong>
                     ${transferData.old_agent ? ` (taking over from ${transferData.old_agent.name})` : ''}`;
                
                notificationContent = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 max-w-md text-center">
                        <div class="flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-green-700 font-medium">${headerText}</span>
                        </div>
                        <p class="text-green-600 text-sm">
                            ${messageText}
                        </p>
                    </div>
                `;
                
                // Update status bar with new agent
                updateChatStatus(null, true, transferData.new_agent.name);
                
                // Show success notification
                const toastMessage = isNewAssignment ? 
                    `${transferData.new_agent.name} joined the conversation` :
                    `Connected to ${transferData.new_agent.name}`;
                showTransferToast('success', toastMessage);
                
            } else {
                console.log('⏳ Waiting for agent assignment');
                // Agent disconnected, waiting for new agent
                const isInitialTransfer = transferData.transfer_reason === 'finding_agent';
                const waitingMessage = isInitialTransfer ? 
                    'Finding a new agent to assist you. Please hold on...' : 
                    'Finding another agent to assist you. Please hold on...';
                
                notificationContent = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 max-w-md text-center">
                        <div class="flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-yellow-500 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="text-yellow-700 font-medium">Finding Agent...</span>
                        </div>
                        <p class="text-yellow-600 text-sm">
                            ${waitingMessage}
                        </p>
                    </div>
                `;
                
                // Update status bar to show waiting
                const statusDiv = document.getElementById('chat-status');
                statusDiv.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse"></div>
                            <span class="text-yellow-700 font-medium">Finding a new agent to assist you...</span>
                        </div>
                    </div>
                `;
                
                // Show waiting notification
                showTransferToast('info', 'Finding a new agent...');
            }
            
            transferNotification.innerHTML = notificationContent;
            messagesContainer.appendChild(transferNotification);
            console.log('➕ Added new transfer notification to messages container');
            scrollToBottom();
        }

        // Helper function to clear transfer notifications (for debugging)
        function clearTransferNotifications() {
            const messagesContainer = document.getElementById('messages-container');
            const notifications = messagesContainer.querySelectorAll('[data-transfer-notification]');
            console.log('🧹 Manually clearing', notifications.length, 'transfer notifications');
            notifications.forEach(notification => notification.remove());
        }

        function showTransferToast(type, message) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'info' ? 'bg-blue-500' : 'bg-yellow-500';
            toast.className += ` ${bgColor} text-white`;
            
            const icon = type === 'success' ? '✅' : type === 'info' ? 'ℹ️' : '⏳';
            
            toast.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-2">${icon}</span>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }
    </script>
    @endpush
</x-user-layout>
