<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class ClearAllData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:clear-all {--force : Force deletion without confirmation}';

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
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL conversations and messages. Are you sure?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->info('Clearing all data...');

        try {
            // Get counts before deletion
            $messageCount = Message::count();
            $conversationCount = Conversation::count();

            $this->info("Found {$messageCount} messages and {$conversationCount} conversations");

            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Delete all messages first
            $this->info('Deleting messages...');
            DB::table('messages')->truncate();

            // Delete all conversations
            $this->info('Deleting conversations...');
            DB::table('conversations')->truncate();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('✅ Successfully deleted all conversations and messages!');
            $this->info("Deleted: {$messageCount} messages, {$conversationCount} conversations");

        } catch (\Exception $e) {
            // Make sure to re-enable foreign key checks even on error
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $fkException) {
                // Ignore FK re-enable errors
            }
            $this->error('❌ Error occurred while clearing data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
