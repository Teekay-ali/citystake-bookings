<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'partial' = a weekly-plan booking that is prepaid week-by-week (not the old
        // unpaid "pending"; those are eliminated - bookings settle before check-in).
        DB::statement("ALTER TABLE bookings MODIFY payment_status ENUM('pending','paid','partial','refunded') NOT NULL DEFAULT 'pending'");

        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('payment_plan', ['full', 'weekly'])->default('full')->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payment_plan');
        });
        DB::statement("ALTER TABLE bookings MODIFY payment_status ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending'");
    }
};
