<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_transferred')->default(false)->after('unread_count');
            $table->integer('transfer_count')->default(0)->after('is_transferred');
            $table->timestamp('last_transferred_at')->nullable()->after('transfer_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['is_transferred', 'transfer_count', 'last_transferred_at']);
        });
    }
};
