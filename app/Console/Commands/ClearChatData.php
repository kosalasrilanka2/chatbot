<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class ClearChatData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:clear {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all existing conversations and messages from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL conversations and messages. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Clearing chat data...');

        // Get counts before deletion
        $messageCount = Message::count();
        $conversationCount = Conversation::count();

        $this->info("Found {$messageCount} messages and {$conversationCount} conversations");

        // Delete messages first (due to foreign key constraints)
        if ($messageCount > 0) {
            $this->line('Deleting messages...');
            Message::query()->delete();
            $this->info("✅ Deleted {$messageCount} messages");
        }

        // Delete conversations
        if ($conversationCount > 0) {
            $this->line('Deleting conversations...');
            Conversation::query()->delete();
            $this->info("✅ Deleted {$conversationCount} conversations");
        }

        if ($messageCount === 0 && $conversationCount === 0) {
            $this->info('No data to clear - database is already empty');
        } else {
            $this->info('🎉 Chat data cleared successfully!');
        }

        return 0;
    }
}
