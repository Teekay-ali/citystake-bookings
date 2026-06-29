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
        Schema::table('notifications', function (Blueprint $table) {
            // Speeds up the unread-count query (notifiable + read_at IS NULL)
            // and the prune sweep (read_at < cutoff).
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_unread_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_unread_index');
        });
    }
};
