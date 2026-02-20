<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bookings table indexes
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['check_in', 'check_out']);
            $table->index('created_at');
        });

        // Units table indexes
        Schema::table('units', function (Blueprint $table) {
            $table->index('unit_type_id');
            $table->index('is_available');
        });

        // Unit Types table indexes
        Schema::table('unit_types', function (Blueprint $table) {
            $table->index('building_id');
            $table->index('is_active');
        });

        // Buildings table indexes
        Schema::table('buildings', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['check_in', 'check_out']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['unit_type_id']);
            $table->dropIndex(['is_available']);
        });

        Schema::table('unit_types', function (Blueprint $table) {
            $table->dropIndex(['building_id']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['slug']);
        });
    }
};
