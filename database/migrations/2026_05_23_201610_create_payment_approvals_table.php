<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', [
                'salary', 'bonus', 'vendor_payment',
                'utility', 'maintenance', 'miscellaneous',
            ]);
            $table->string('custom_type')->nullable();
            $table->string('recipient_name');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined', 'paid'])->default('pending');
            $table->text('ceo_comment')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_evidence')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_approvals');
    }
};
