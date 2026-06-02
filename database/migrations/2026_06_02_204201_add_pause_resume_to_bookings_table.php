<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('checked_out_at');
            $table->foreignId('paused_by')->nullable()->constrained('users')->nullOnDelete()->after('paused_at');
            $table->date('paused_departure')->nullable()->after('paused_by');
            $table->integer('remaining_nights')->nullable()->after('paused_departure');
            $table->timestamp('resumed_at')->nullable()->after('remaining_nights');
            $table->foreignId('resumed_by')->nullable()->constrained('users')->nullOnDelete()->after('resumed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paused_by');
            $table->dropConstrainedForeignId('resumed_by');
            $table->dropColumn([
                'paused_at', 'paused_departure',
                'remaining_nights', 'resumed_at',
            ]);
        });
    }
};
