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
                    <!-- Agent Profile Section -->
                    @if($agent)
                    <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{ $agent->name }}
                                </h3>
                                <p class="text-sm text-gray-600">{{ $agent->email }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-medium text-gray-700 flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                    Skills:
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    @php
                                        $skillColors = [
                                            'language' => [
                                                'SI' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'TI' => 'bg-sky-100 text-sky-700 border-sky-200', 
                                                'EN' => 'bg-violet-100 text-violet-700 border-violet-200'
                                            ],
                                            'domain' => [
                                                'FINANCE' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'HR' => 'bg-rose-100 text-rose-700 border-rose-200',
                                                'IT' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'NETWORK' => 'bg-slate-100 text-slate-700 border-slate-200'
                                            ]
                                        ];
                                        
                                        $skillIcons = [
                                            'language' => '🌐',
                                            'domain' => '💼'
                                        ];
                                    @endphp
                                    
                                    @foreach($agent->skills as $skill)
                                        @php
                                            $colorClass = $skillColors[$skill->skill_type][$skill->skill_code] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                            $displayName = $skill->skill_code;
                                            if ($skill->skill_type === 'language') {
                                                $displayName = [
                                                    'SI' => 'Sinhala',
                                                    'TI' => 'Tamil', 
                                                    'EN' => 'English'
                                                ][$skill->skill_code] ?? $skill->skill_code;
                                            }
                                            $icon = $skillIcons[$skill->skill_type] ?? '⚡';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $colorClass }} shadow-sm">
                                            <span class="mr-1">{{ $icon }}</span>
                                            {{ $displayName }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column: Agent Controls & Conversations -->
                        <div>
                            <!-- Agent Status Controls -->
                            <div class="mb-6">
                                <h3 class="text-lg font-medium mb-4">Agent Status</h3>
                                <div class="flex items-center space-x-4">
                                    <button id="status-toggle-btn" onclick="toggleStatus()" 
                                            class="px-6 py-3 text-white rounded-lg font-medium transition-colors duration-200 shadow-sm hover:shadow-md">
                                        <span id="status-text">Online</span>
                                    </button>
                                    <div class="flex items-center">
                                        <div id="status-indicator" class="w-3 h-3 rounded-full mr-2"></div>
                                        <span id="status-description" class="text-sm text-gray-600">Available for new conversations</span>
                                    </div>
                                </div>
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
                                <h3 class="text-lg font-medium mb-4">Conversations</h3>
                                <div id="conversations-list" class="space-y-2">
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
                                <div id="message-input-area" class="p-6 border-t bg-white rounded-b-lg">
                                    <div class="flex space-x-2">
                                        <input type="text" 
                                               id="message-input" 
                                               placeholder="Type your response..." 
                                               class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               onkeypress="if(event.key === 'Enter') sendMessage()">
                                        <button id="send-button"
                                                onclick="sendMessage()" 
                                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            Send
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Read-only message for closed conversations -->
                                <div id="read-only-message" class="p-4 border-t bg-gray-50 text-center text-gray-600 hidden rounded-b-lg">
                                    <div class="flex items-center justify-center space-x-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        <span>This conversation is closed and cannot receive new messages</span>
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

                // Listen for new conversations
                agentsChannel.listen('.conversation.new', (e) => {
                    console.log('🔔 New conversation created:', e);
                    
                    // Show notification
                    showNotification(`New conversation from ${e.conversation.user.name}`);
                    
                    // Refresh conversations list to show the new conversation
                    loadConversations();
                    
                    // Update unread count
                    updateUnreadCount();
                });

                // Listen for conversation closures
                agentsChannel.listen('.conversation.closed', (e) => {
                    console.log('🔒 Conversation closed:', e);
                    
                    // Show notification
                    showNotification(`Conversation with ${e.conversation.user.name} was closed`);
                    
                    // Refresh conversations list to update the status
                    loadConversations();
                    
                    // If this is the currently open conversation, switch to read-only mode
                    if (currentConversationId == e.conversation.id) {
                        isReadOnlyMode = true;
                        updateMessageInputState();
                        
                        // Update the title to show it's closed
                        const titleElement = document.getElementById('conversation-title');
                        if (titleElement) {
                            titleElement.textContent = `${titleElement.textContent} (Read Only - Closed)`;
                            titleElement.classList.add('text-gray-600');
                        }
                        
                        // Add visual indicator to conversation view
                        const conversationView = document.getElementById('conversation-view');
                        if (conversationView) {
                            conversationView.classList.add('opacity-75');
                        }
                    }
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

                        agentChannel.listen('.force-logout', (e) => {
                            console.log('🔒 Force logout received:', e);
                            
                            // Stop heartbeat
                            stopHeartbeat();
                            
                            // Show warning message
                            alert('You have been logged out due to inactivity. Please log in again.');
                            
                            // Redirect to login page
                            window.location.href = '/login';
                        });
                    @else
                        console.warn('⚠️ No agent found for current user');
                    @endif
                @endauth
            }

            // Start initialization
            initializeEcho();

            // Initialize agent presence tracking
            initializeAgentPresence();

            // Load initial data
            loadConversations();
            updateUnreadCount();

            // Set initial status based on agent's current status in database
            @if($agent)
                currentStatus = '{{ $agent->status }}';
                updateStatusUI('{{ $agent->status }}');
            @else
                // Set initial status to online if no specific status found
                updateAgentStatus('online');
                updateStatusUI('online');
            @endif
        });

        let currentStatus = 'online'; // Initialize as online
        let heartbeatInterval = null;
        let isPageUnloading = false;

        // Agent presence tracking system
        function initializeAgentPresence() {
            console.log('🔄 Initializing agent presence tracking...');
            
            // Start heartbeat to maintain presence
            startHeartbeat();
            
            // Handle browser close/refresh
            window.addEventListener('beforeunload', function(e) {
                console.log('🚪 Browser closing, setting agent offline...');
                isPageUnloading = true;
                setAgentOfflineSync();
            });
            
            // Handle page visibility changes (tab switching, minimizing)
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    console.log('👁️ Page visible, resuming heartbeat...');
                    startHeartbeat();
                } else {
                    console.log('🙈 Page hidden, reducing heartbeat...');
                    // Don't stop heartbeat completely, just reduce frequency
                }
            });
            
            // Handle network reconnection
            window.addEventListener('online', function() {
                console.log('🌐 Network reconnected, resuming agent presence...');
                startHeartbeat();
                // Update status to online if it was online before
                if (currentStatus === 'online') {
                    updateAgentStatus('online');
                }
            });
            
            // Handle network disconnection
            window.addEventListener('offline', function() {
                console.log('📡 Network disconnected, agent will appear offline...');
                stopHeartbeat();
            });
        }

        function startHeartbeat() {
            // Clear existing interval
            if (heartbeatInterval) {
                clearInterval(heartbeatInterval);
            }
            
            // Send heartbeat every 15 seconds
            heartbeatInterval = setInterval(function() {
                if (!isPageUnloading) {
                    sendHeartbeat();
                }
            }, 15000); // 15 seconds
            
            // Send immediate heartbeat
            sendHeartbeat();
        }

        function stopHeartbeat() {
            if (heartbeatInterval) {
                clearInterval(heartbeatInterval);
                heartbeatInterval = null;
            }
        }

        function sendHeartbeat() {
            fetch('/agent/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    status: currentStatus,
                    timestamp: Date.now()
                })
            })
            .then(response => {
                if (!response.ok) {
                    console.warn('⚠️ Heartbeat failed:', response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('💓 Heartbeat sent successfully at', new Date().toLocaleTimeString());
            })
            .catch(error => {
                console.warn('⚠️ Heartbeat error:', error);
            });
        }

        function setAgentOfflineSync() {
            // Synchronous request for browser close event
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/agent/set-offline', false); // false = synchronous
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            try {
                xhr.send(JSON.stringify({
                    reason: 'browser_close'
                }));
                console.log('🔌 Agent set offline due to browser close');
            } catch (error) {
                console.error('❌ Failed to set agent offline:', error);
            }
        }

        function toggleStatus() {
            const newStatus = currentStatus === 'online' ? 'busy' : 'online';
            updateAgentStatus(newStatus);
        }

        function updateAgentStatus(status) {
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
                if (data.message) {
                    currentStatus = status;
                    updateStatusUI(status);
                    showNotification(`Status updated to ${status}`);
                } else {
                    showNotification('Failed to update status', 'error');
                }
            })
            .catch(error => {
                console.error('Error updating status:', error);
                showNotification('Failed to update status', 'error');
            });
        }

        function updateStatusUI(status) {
            const button = document.getElementById('status-toggle-btn');
            const statusText = document.getElementById('status-text');
            const statusIndicator = document.getElementById('status-indicator');
            const statusDescription = document.getElementById('status-description');

            if (status === 'online') {
                button.className = 'px-6 py-3 text-white rounded-lg font-medium transition-colors duration-200 shadow-sm hover:shadow-md bg-green-500 hover:bg-green-600';
                statusText.textContent = 'Online';
                statusIndicator.className = 'w-3 h-3 rounded-full mr-2 bg-green-500 animate-pulse';
                statusDescription.textContent = 'Available for new conversations';
            } else if (status === 'busy') {
                button.className = 'px-6 py-3 text-white rounded-lg font-medium transition-colors duration-200 shadow-sm hover:shadow-md bg-amber-500 hover:bg-amber-600';
                statusText.textContent = 'Busy';
                statusIndicator.className = 'w-3 h-3 rounded-full mr-2 bg-amber-500';
                statusDescription.textContent = 'Handling current conversations only';
            }
        }

        function updateStatus(status) {
            // Keep this function for backward compatibility but redirect to new function
            updateAgentStatus(status);
        }

        function loadConversations() {
            fetch('/agent/conversations')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Conversations data received:', data);
                    const container = document.getElementById('conversations-list');
                    container.innerHTML = '';
                    
                    // Check for error response
                    if (data.error) {
                        container.innerHTML = `<div class="text-red-600 p-4">${data.error}</div>`;
                        return;
                    }
                    
                    // Handle both assigned and waiting conversations
                    const assignedConversations = data.assigned_conversations || [];
                    const waitingConversations = data.waiting_conversations || [];
                    
                    console.log('Assigned conversations:', assignedConversations.length);
                    console.log('Waiting conversations:', waitingConversations.length);
                    
                    // Combine all conversations and sort by last_activity (newest first)
                    const allConversations = [
                        ...assignedConversations.map(conv => ({ ...conv, isAssigned: true })),
                        ...waitingConversations.map(conv => ({ ...conv, isAssigned: false }))
                    ].sort((a, b) => new Date(b.last_activity) - new Date(a.last_activity));
                    
                    console.log('All conversations combined:', allConversations.length);
                    
                    if (allConversations.length > 0) {
                        allConversations.forEach(conversation => {
                            const div = createConversationDiv(conversation, conversation.isAssigned);
                            container.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">No conversations available</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading conversations:', error);
                    const container = document.getElementById('conversations-list');
                    container.innerHTML = '<div class="text-red-600 p-4">Error loading conversations. Check console for details.</div>';
                });
        }
        
        function createConversationDiv(conversation, isAssigned) {
            const div = document.createElement('div');
            const isClosed = conversation.status === 'closed';
            const canAccess = isAssigned || conversation.agent_id === null; // Can access if assigned to me or unassigned
            
            // Apply different styling for closed conversations and access restrictions
            const baseClasses = 'border rounded p-3 mb-2 transition-all duration-200';
            let statusClasses;
            
            if (isClosed) {
                statusClasses = 'bg-gray-100 border-gray-300 opacity-75 cursor-default';
            } else if (!canAccess) {
                statusClasses = 'bg-red-50 border-red-200 opacity-60 cursor-not-allowed';
            } else {
                statusClasses = `hover:bg-gray-50 cursor-pointer hover:shadow-sm ${isAssigned ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'}`;
            }
            
            div.className = `${baseClasses} ${statusClasses}`;
            
            // Format last activity time
            const lastActivity = new Date(conversation.last_activity);
            const now = new Date();
            const diffMinutes = Math.floor((now - lastActivity) / (1000 * 60));
            
            let timeDisplay;
            if (diffMinutes < 1) {
                timeDisplay = 'Just now';
            } else if (diffMinutes < 60) {
                timeDisplay = `${diffMinutes}m ago`;
            } else if (diffMinutes < 1440) {
                timeDisplay = `${Math.floor(diffMinutes / 60)}h ago`;
            } else {
                timeDisplay = lastActivity.toLocaleDateString();
            }
            
            // Status badge styling
            const getStatusBadge = () => {
                if (isClosed) {
                    return '<span class="px-2 py-1 bg-gray-200 text-gray-600 text-xs rounded-full font-medium">CLOSED</span>';
                } else if (!canAccess) {
                    return '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">RESTRICTED</span>';
                } else if (isAssigned) {
                    return '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">ASSIGNED</span>';
                } else {
                    return '<span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs rounded-full font-medium">WAITING</span>';
                }
            };
            
            // Pick up button for waiting conversations
            const pickupButton = (!isAssigned && !isClosed && canAccess) 
                ? `<button onclick="assignConversation(${conversation.id})" 
                          class="ml-2 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors">
                      Pick Up
                   </button>`
                : '';
            
            // Click handler - only allow if conversation can be accessed
            const clickHandler = canAccess ? 
                (isClosed ? `openConversation(${conversation.id}, true)` : `openConversation(${conversation.id})`) : 
                'showNotification("You cannot access conversations assigned to other agents")';
            
            div.innerHTML = `
                <div class="flex items-center justify-between">
                    <div onclick="${clickHandler}" 
                         class="flex-1 flex items-center space-x-3 ${canAccess ? (isClosed ? 'cursor-default' : 'cursor-pointer') : 'cursor-not-allowed'}">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium truncate ${isClosed ? 'text-gray-500' : (!canAccess ? 'text-red-500' : 'text-gray-900')}">${conversation.user.name}</h4>
                        </div>
                        <div class="flex items-center space-x-2">
                            ${getStatusBadge()}
                            ${conversation.unread_count > 0 && canAccess ? `<span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-green-500 rounded-full animate-pulse">${conversation.unread_count}</span>` : ''}
                            <span class="text-xs ${isClosed ? 'text-gray-400' : (!canAccess ? 'text-red-400' : 'text-gray-500')} whitespace-nowrap">${timeDisplay}</span>
                        </div>
                    </div>
                    ${pickupButton}
                </div>
            `;
            
            return div;
        }

        let currentConversationId = null;
        let conversationChannel = null;
        let isReadOnlyMode = false;

        function openConversation(conversationId, readOnly = false) {
            currentConversationId = conversationId;
            isReadOnlyMode = readOnly;
            
            // Show conversation view
            document.getElementById('conversation-view').classList.remove('hidden');
            
            // Load conversation details
            fetch(`/agent/conversation/${conversationId}`)
                .then(response => response.json())
                .then(conversation => {
                    const titleElement = document.getElementById('conversation-title');
                    const title = conversation.title || `Conversation with ${conversation.user.name}`;
                    titleElement.textContent = readOnly ? `${title} (Read Only - Closed)` : title;
                    
                    // Apply read-only styling to the conversation view
                    const conversationView = document.getElementById('conversation-view');
                    if (readOnly) {
                        conversationView.classList.add('opacity-75');
                        titleElement.classList.add('text-gray-600');
                    } else {
                        conversationView.classList.remove('opacity-75');
                        titleElement.classList.remove('text-gray-600');
                    }
                })
                .catch(error => console.error('Error loading conversation:', error));
            
            // Load messages
            loadMessages(conversationId);
            
            // Update message input visibility based on read-only mode
            updateMessageInputState();
            
            // Subscribe to conversation channel for real-time updates (only if not read-only)
            if (!readOnly) {
                subscribeToConversation(conversationId);
            }
        }

        function closeConversation() {
            document.getElementById('conversation-view').classList.add('hidden');
            
            // Leave conversation channel
            if (conversationChannel) {
                window.Echo.leaveChannel(`private-conversation.${currentConversationId}`);
                conversationChannel = null;
            }
            
            currentConversationId = null;
            isReadOnlyMode = false; // Reset read-only mode
        }

        function updateMessageInputState() {
            const messageInputArea = document.getElementById('message-input-area');
            const readOnlyMessage = document.getElementById('read-only-message');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            
            if (isReadOnlyMode) {
                // Hide input area and show read-only message
                messageInputArea.classList.add('hidden');
                readOnlyMessage.classList.remove('hidden');
            } else {
                // Show input area and hide read-only message
                messageInputArea.classList.remove('hidden');
                readOnlyMessage.classList.add('hidden');
                
                // Ensure input and button are enabled
                messageInput.disabled = false;
                sendButton.disabled = false;
            }
        }

        function loadMessages(conversationId) {
            fetch(`/agent/conversation/${conversationId}/messages`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.error || 'Failed to load messages');
                        });
                    }
                    return response.json();
                })
                .then(messages => {
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '';
                    
                    messages.forEach(message => {
                        appendMessage(message);
                    });
                    
                    scrollToBottom();
                    
                    // Refresh conversations list to update unread counts
                    loadConversations();
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    showNotification(error.message || 'Failed to load messages');
                    
                    // Clear messages container and show error message
                    const container = document.getElementById('messages-container');
                    container.innerHTML = `
                        <div class="flex items-center justify-center h-full text-gray-500">
                            <div class="text-center">
                                <div class="text-2xl mb-2">🔒</div>
                                <div class="text-lg font-medium mb-1">Access Restricted</div>
                                <div class="text-sm">${error.message || 'You cannot view messages for this conversation'}</div>
                            </div>
                        </div>
                    `;
                });
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
            
            if (isReadOnlyMode) {
                showNotification('Cannot send messages to closed conversations');
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
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error || 'Failed to send message');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.message === 'Message sent successfully') {
                    input.value = '';
                    
                    // Refresh conversations list to update unread counts
                    loadConversations();
                    
                    // Message will appear via real-time WebSocket subscription
                    // No need to add immediately since real-time is working
                } else {
                    console.error('Error sending message:', data);
                    showNotification('Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showNotification(error.message || 'Failed to send message');
            });
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
