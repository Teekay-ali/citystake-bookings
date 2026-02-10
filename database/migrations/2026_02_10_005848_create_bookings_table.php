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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('property_id')->constrained();
            $table->foreignId('user_id')->constrained();

            $table->date('check_in');
            $table->date('check_out');
            $table->integer('nights');
            $table->integer('guests');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('cleaning_fee', 10, 2);
            $table->decimal('service_charge', 10, 2);
            $table->decimal('total_amount', 10, 2);

            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');

            $table->string('paystack_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('special_requests')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['property_id', 'check_in', 'check_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
