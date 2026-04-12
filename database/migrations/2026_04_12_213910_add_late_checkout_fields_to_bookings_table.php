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
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('late_checkout_requested')->default(false)->after('checkin_notes');
            $table->enum('late_checkout_status', ['pending', 'approved', 'rejected', 'settled'])->nullable()->after('late_checkout_requested');
            $table->decimal('late_checkout_fee', 10, 2)->nullable()->after('late_checkout_status');
            $table->foreignId('late_checkout_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('late_checkout_fee');
            $table->timestamp('late_checkout_approved_at')->nullable()->after('late_checkout_approved_by');
            $table->timestamp('late_checkout_settled_at')->nullable()->after('late_checkout_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('late_checkout_approved_by');
            $table->dropColumn([
                'late_checkout_requested',
                'late_checkout_status',
                'late_checkout_fee',
                'late_checkout_approved_at',
                'late_checkout_settled_at',
            ]);
        });
    }
};
