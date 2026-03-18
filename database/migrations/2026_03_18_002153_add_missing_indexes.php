<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // bookings: unit_id alone (for lookups by unit without date range)
        // unit_type_id and building_id (for admin filters and reporting)
        // booking_reference (for search — it's unique but needs a fast lookup index)
        // paid_at (for revenue reporting queries)
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('unit_id');
            $table->index('unit_type_id');
            $table->index('building_id');
            $table->index('paid_at');
        });

        // unit_types: slug for route model binding lookups
        Schema::table('unit_types', function (Blueprint $table) {
            $table->index('slug');
        });

        // blocked_dates: blocked_from and blocked_to individually
        // for range queries that don't always use both columns together
        Schema::table('blocked_dates', function (Blueprint $table) {
            $table->index('blocked_from');
            $table->index('blocked_to');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['unit_id']);
            $table->dropIndex(['unit_type_id']);
            $table->dropIndex(['building_id']);
            $table->dropIndex(['paid_at']);
        });

        Schema::table('unit_types', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });

        Schema::table('blocked_dates', function (Blueprint $table) {
            $table->dropIndex(['blocked_from']);
            $table->dropIndex(['blocked_to']);
        });
    }
};
