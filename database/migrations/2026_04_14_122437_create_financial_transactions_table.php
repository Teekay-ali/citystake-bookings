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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', [
                'booking',
                'late_checkout',
                'maintenance',
                'procurement',
                'manual_income',
                'manual_expense',
            ]);
            // Polymorphic link to source record
            $table->nullableMorphs('reference');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', [
                'cash', 'bank_transfer', 'pos', 'paystack', 'other'
            ])->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
