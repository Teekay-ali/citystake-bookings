<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE financial_transactions MODIFY COLUMN payment_method ENUM('cash','bank_transfer','pos','paystack','monnify','other') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE financial_transactions MODIFY COLUMN payment_method ENUM('cash','bank_transfer','pos','paystack','other') NULL");
    }
};
