<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;

class FixUnreadCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:fix-unread-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix incorrect unread counts that may include system messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Fixing unread counts...');
        
        $conversations = Conversation::whereNotNull('agent_id')->get();
        $fixed = 0;
        $total = $conversations->count();
        
        if ($total === 0) {
            $this->info('No conversations with agents found.');
            return 0;
        }
        
        $this->info("Checking {$total} conversations...");
        
        foreach ($conversations as $conversation) {
            $oldCount = $conversation->unread_count;
            $newCount = $conversation->recalculateUnreadCount();
            
            if ($oldCount !== $newCount) {
                $fixed++;
                $this->line("  Fixed conversation {$conversation->id}: {$oldCount} → {$newCount}");
            }
        }
        
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} conversations with incorrect unread counts");
        } else {
            $this->info("✅ All unread counts are already correct");
        }
        
        return 0;
    }
}
