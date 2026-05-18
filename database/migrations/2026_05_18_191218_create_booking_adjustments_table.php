<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->enum('amount_type', ['fixed', 'percentage']);
            $table->decimal('amount_value', 10, 2); // the raw input (e.g. 20 for 20%, or 10000 for ₦10,000)
            $table->decimal('amount_naira', 12, 2);  // always the resolved ₦ amount
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });

        // Extend the financial_transactions category enum
        DB::statement("ALTER TABLE financial_transactions MODIFY COLUMN category ENUM(
            'booking','late_checkout','maintenance','procurement',
            'manual_income','manual_expense','goodwill_adjustment'
        )");
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_adjustments');

        DB::statement("ALTER TABLE financial_transactions MODIFY COLUMN category ENUM(
            'booking','late_checkout','maintenance','procurement',
            'manual_income','manual_expense'
        )");
    }
};
