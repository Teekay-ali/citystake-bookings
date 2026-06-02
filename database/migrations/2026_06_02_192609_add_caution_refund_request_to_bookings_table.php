<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('caution_refund_requested')->default(false)->after('caution_fee_deduction_reason');
            $table->timestamp('caution_refund_requested_at')->nullable()->after('caution_refund_requested');
            $table->foreignId('caution_refund_requested_by')->nullable()->constrained('users')->nullOnDelete()->after('caution_refund_requested_at');
            $table->enum('caution_refund_action', ['full_refund', 'partial_deduction', 'full_forfeit'])->nullable()->after('caution_refund_requested_by');
            $table->string('caution_refund_reason')->nullable()->after('caution_refund_action');
            $table->decimal('caution_refund_deduction_amount', 10, 2)->nullable()->after('caution_refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caution_refund_requested_by');
            $table->dropColumn([
                'caution_refund_requested',
                'caution_refund_requested_at',
                'caution_refund_action',
                'caution_refund_reason',
                'caution_refund_deduction_amount',
            ]);
        });
    }
};
