<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_message_recipients', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'smr_user_read_idx');
            $table->index('staff_message_id', 'smr_message_idx');
        });
    }

    public function down(): void
    {
        Schema::table('staff_message_recipients', function (Blueprint $table) {
            $table->dropIndex('smr_user_read_idx');
            $table->dropIndex('smr_message_idx');
        });
    }
};
