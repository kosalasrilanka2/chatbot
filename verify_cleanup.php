<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$conversationCount = App\Models\Conversation::count();
$messageCount = App\Models\Message::count();
$userCount = App\Models\User::count();
$agentCount = App\Models\Agent::count();

echo "Database Status After Cleanup:\n";
echo "==============================\n";
echo "Conversations: {$conversationCount}\n";
echo "Messages: {$messageCount}\n";
echo "Users: {$userCount}\n";
echo "Agents: {$agentCount}\n";
echo "\n";

if ($conversationCount === 0 && $messageCount === 0) {
    echo "✅ SUCCESS: All conversations and messages have been deleted!\n";
    echo "👥 Users and agents are preserved and ready for new conversations.\n";
} else {
    echo "❌ WARNING: Some data still remains in the database.\n";
}
