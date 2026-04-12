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
            $table->timestamp('checked_in_at')->nullable()->after('cancelled_at');
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete()->after('checked_in_at');
            $table->decimal('amount_received', 10, 2)->nullable()->after('checked_in_by');
            $table->string('checkin_payment_method')->nullable()->after('amount_received');
            $table->text('checkin_notes')->nullable()->after('checkin_payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropColumn(['checked_in_at', 'amount_received', 'checkin_payment_method', 'checkin_notes']);
        });
    }
};
