<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('caution_fee_deduction', 10, 2)->nullable()->after('caution_fee_refunded_by');
            $table->string('caution_fee_deduction_reason')->nullable()->after('caution_fee_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['caution_fee_deduction', 'caution_fee_deduction_reason']);
        });
    }
};
