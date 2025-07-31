<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;
use App\Models\Message;

class ClearAllConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:clear {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all conversations and messages from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get counts before deletion
        $messageCount = Message::count();
        $conversationCount = Conversation::count();

        $this->info("Found {$messageCount} messages and {$conversationCount} conversations to delete.");

        if ($messageCount === 0 && $conversationCount === 0) {
            $this->info('No data to delete. Database is already clean.');
            return 0;
        }

        // Ask for confirmation unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('This will permanently delete ALL conversations and messages. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Deleting all messages and conversations...');

        // Delete messages first (due to foreign key constraints)
        $deletedMessages = Message::count();
        Message::query()->delete();
        $this->info("✅ Deleted {$deletedMessages} messages");

        // Delete conversations
        $deletedConversations = Conversation::count();
        Conversation::query()->delete();
        $this->info("✅ Deleted {$deletedConversations} conversations");

        // Reset auto-increment IDs to start fresh
        DB::statement('ALTER TABLE messages AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE conversations AUTO_INCREMENT = 1');
        $this->info("✅ Reset auto-increment counters");

        $this->info('🎉 All conversations and messages have been successfully deleted!');
        $this->warn('Note: This does not delete users or agents, only conversations and messages.');

        return 0;
    }
}
