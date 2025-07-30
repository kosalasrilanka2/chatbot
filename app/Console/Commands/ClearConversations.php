<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:clear {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all conversations and messages (keeps users and agents)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗑️  Clearing all conversations and messages...');
        $this->info('');

        // Count current data
        $conversationCount = Conversation::count();
        $messageCount = Message::count();

        if ($conversationCount === 0 && $messageCount === 0) {
            $this->info('✅ No conversations or messages to clear.');
            return 0;
        }

        $this->info("📊 Current data:");
        $this->info("  • Conversations: {$conversationCount}");
        $this->info("  • Messages: {$messageCount}");
        $this->info('');

        // Confirmation unless --force flag is used
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete ALL conversations and messages? This cannot be undone.')) {
                $this->info('❌ Operation cancelled.');
                return 1;
            }
        }

        try {
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Clear messages first (due to foreign key constraints)
            Message::truncate();
            $this->info('✅ Cleared all messages');
            
            // Clear conversations
            Conversation::truncate();
            $this->info('✅ Cleared all conversations');
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('');
            $this->info('🎉 Successfully cleared all conversations and messages!');
            $this->info('');
            $this->info('👥 Users and agents remain intact and ready for new conversations.');
            $this->info('💬 Users can now create new conversations that will be auto-assigned to available agents.');

        } catch (\Exception $e) {
            $this->error('❌ Error clearing data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
