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
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PR-20260413-XXXX
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();

            // Optional vendor link or manual entry
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->string('supplier_email')->nullable();

            // Approval chain
            $table->foreignId('accountant_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accountant_approved_at')->nullable();

            $table->foreignId('ceo_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ceo_approved_at')->nullable();

            $table->foreignId('purchased_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('purchased_at')->nullable();

            $table->foreignId('receipt_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('receipt_confirmed_at')->nullable();

            // Details
            $table->string('title');
            $table->text('justification')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->enum('status', [
                'pending',             // submitted, awaiting accountant
                'accountant_approved', // accountant signed off
                'ceo_approved',        // CEO approved
                'purchased',           // head of procurement bought items
                'completed',           // manager confirmed receipt
                'rejected',
            ])->default('pending');

            $table->string('rejection_reason')->nullable();
            $table->string('rejected_by_role')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_requests');
    }
};
