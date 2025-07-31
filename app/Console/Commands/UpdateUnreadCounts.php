<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;

class UpdateUnreadCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:update-unread-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update unread message counts for all conversations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating unread counts for all conversations...');
        
        $conversations = Conversation::all();
        $updated = 0;
        
        foreach ($conversations as $conversation) {
            $oldCount = $conversation->unread_count;
            $newCount = $conversation->updateUnreadCount();
            
            if ($oldCount !== $newCount) {
                $updated++;
                $this->line("Conversation {$conversation->id}: {$oldCount} → {$newCount}");
            }
        }
        
        $this->info("Updated {$updated} conversations out of {$conversations->count()} total.");
        return 0;
    }
}
