<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Chatbot System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen">
    <div x-data="adminDashboard()" x-init="init()" class="container mx-auto p-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-tachometer-alt text-blue-600"></i>
                        Admin Dashboard
                    </h1>
                    <p class="text-gray-600 mt-2">Real-time system monitoring</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Last updated:</div>
                    <div class="text-lg font-semibold" x-text="lastUpdated"></div>
                    <div class="flex items-center mt-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-2"></div>
                        <span class="text-sm text-green-600">Live</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" x-text="stats.total_users || 0"></div>
                        <div class="text-sm text-gray-600">Total Users</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-headset text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" x-text="stats.total_agents || 0"></div>
                        <div class="text-sm text-gray-600">Total Agents</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-comments text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" x-text="stats.active_conversations || 0"></div>
                        <div class="text-sm text-gray-600">Active Chats</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" x-text="stats.waiting_conversations || 0"></div>
                        <div class="text-sm text-gray-600">Waiting for Agent</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-envelope text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" x-text="stats.total_messages_today || 0"></div>
                        <div class="text-sm text-gray-600">Messages Today</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800" x-text="stats.unread_messages || 0"></div>
                        <div class="text-sm text-gray-600">Unread Messages</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agent Workload Distribution -->
        <div class="bg-white rounded-lg shadow-md mb-6" x-show="assignmentStats && assignmentStats.agent_workload && assignmentStats.agent_workload.length > 0">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                    Agent Workload Distribution
                    <span class="ml-2 bg-purple-100 text-purple-800 text-sm font-medium px-2.5 py-0.5 rounded-full" x-text="assignmentStats?.agent_workload?.length || 0"></span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="agent in (assignmentStats?.agent_workload || [])" :key="agent.agent_email">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-gray-800" x-text="agent.agent_name"></div>
                                <div class="flex items-center text-sm">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                    <span class="text-green-600">Online</span>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600 mb-3" x-text="agent.agent_email"></div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Active Chats:</span>
                                <div class="flex items-center">
                                    <div class="bg-blue-100 text-blue-800 text-sm font-medium px-2 py-1 rounded" x-text="agent.active_conversations"></div>
                                    <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${Math.min((agent.active_conversations / 5) * 100, 100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <template x-if="!assignmentStats?.agent_workload || assignmentStats.agent_workload.length === 0">
                    <div class="text-center py-8">
                        <i class="fas fa-users-slash text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No online agents</p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Active Users and Agents -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Active Users -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-user-circle text-blue-600 mr-2"></i>
                        Active Users
                        <span class="ml-2 bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full" x-text="activeUsers.length"></span>
                    </h2>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto">
                    <template x-if="activeUsers.length === 0">
                        <div class="text-center py-8">
                            <i class="fas fa-user-slash text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No active users</p>
                        </div>
                    </template>
                    
                    <template x-for="user in activeUsers" :key="user.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-3">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                    <span x-text="user.name.charAt(0).toUpperCase()"></span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800" x-text="user.name"></div>
                                    <div class="text-sm text-gray-600" x-text="user.email"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center text-green-600 text-sm mb-1">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                    <span>Online</span>
                                </div>
                                <div class="text-xs text-gray-500" x-text="formatTime(user.last_activity)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Active Agents -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-headset text-green-600 mr-2"></i>
                        Active Agents
                        <span class="ml-2 bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded-full" x-text="activeAgents.length"></span>
                    </h2>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto">
                    <template x-if="activeAgents.length === 0">
                        <div class="text-center py-8">
                            <i class="fas fa-headset text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No active agents</p>
                        </div>
                    </template>
                    
                    <template x-for="agent in activeAgents" :key="agent.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-3">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                    <span x-text="agent.name.charAt(0).toUpperCase()"></span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800" x-text="agent.name"></div>
                                    <div class="text-sm text-gray-600" x-text="agent.email"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center text-sm mb-1" :class="agent.status === 'online' ? 'text-green-600' : agent.status === 'busy' ? 'text-yellow-600' : 'text-gray-600'">
                                    <div class="w-2 h-2 rounded-full mr-2" :class="agent.status === 'online' ? 'bg-green-500' : agent.status === 'busy' ? 'bg-yellow-500' : 'bg-gray-500'"></div>
                                    <span x-text="agent.status.charAt(0).toUpperCase() + agent.status.slice(1)"></span>
                                </div>
                                <div class="text-xs text-gray-500" x-text="formatTime(agent.last_activity)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Recent Conversations -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-history text-gray-600 mr-2"></i>
                    Recent Conversations
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="conversation in recentConversations" :key="conversation.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold mr-3">
                                            <span x-text="conversation.user_name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900" x-text="conversation.user_name"></div>
                                            <div class="text-sm text-gray-500" x-text="conversation.user_email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="conversation.title || 'Untitled'"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                          :class="conversation.status === 'active' ? 'bg-green-100 text-green-800' : conversation.status === 'waiting' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'"
                                          x-text="conversation.status">
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 truncate max-w-xs" x-text="conversation.last_message"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatTime(conversation.last_activity)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function adminDashboard() {
            return {
                activeUsers: [],
                activeAgents: [],
                stats: {},
                assignmentStats: {},
                recentConversations: [],
                lastUpdated: '',
                
                init() {
                    this.loadData();
                    // Refresh data every 30 seconds
                    setInterval(() => {
                        this.loadData();
                    }, 30000);
                },
                
                async loadData() {
                    try {
                        const response = await fetch('/admin/stats');
                        const data = await response.json();
                        
                        this.activeUsers = data.active_users || [];
                        this.activeAgents = data.active_agents || [];
                        this.stats = data.stats || {};
                        this.assignmentStats = data.assignment_stats || {};
                        this.recentConversations = data.recent_conversations || [];
                        this.lastUpdated = new Date().toLocaleTimeString();
                    } catch (error) {
                        console.error('Error loading admin data:', error);
                    }
                },
                
                formatTime(datetime) {
                    if (!datetime) return 'Never';
                    return new Date(datetime).toLocaleString();
                }
            }
        }
    </script>
</body>
</html>
