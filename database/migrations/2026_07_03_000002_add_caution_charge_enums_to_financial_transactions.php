<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE financial_transactions MODIFY category ENUM(
            'booking','late_checkout','caution_fee_deduction','restaurant',
            'maintenance','procurement','manual_income','manual_expense','goodwill_adjustment'
        ) NULL");

        DB::statement("ALTER TABLE financial_transactions MODIFY payment_method ENUM(
            'cash','bank_transfer','pos','paystack','monnify','caution_fee','other'
        ) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE financial_transactions MODIFY category ENUM(
            'booking','late_checkout','maintenance','procurement',
            'manual_income','manual_expense','goodwill_adjustment'
        ) NULL");

        DB::statement("ALTER TABLE financial_transactions MODIFY payment_method ENUM(
            'cash','bank_transfer','pos','paystack','monnify','other'
        ) NULL");
    }
};
