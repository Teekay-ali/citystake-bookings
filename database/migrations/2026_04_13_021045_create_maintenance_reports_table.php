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
        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();

            // Approval chain
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable();

            $table->foreignId('accountant_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accountant_approved_at')->nullable();

            $table->foreignId('ceo_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ceo_approved_at')->nullable();

            $table->foreignId('payment_made_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_made_at')->nullable();

            // Report details
            $table->string('title');
            $table->enum('issue_type', [
                'leakage', 'electrical', 'ac_hvac', 'appliance',
                'plumbing', 'structural', 'carpentry', 'painting', 'other'
            ]);
            $table->text('description');
            $table->string('location')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->date('repair_timeline')->nullable();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();

            // Workflow status
            $table->enum('status', [
                'pending',           // just submitted
                'manager_approved',  // manager signed off
                'accountant_approved', // accountant approved cost
                'ceo_approved',      // CEO approved
                'payment_pending',   // ready for accountant to pay
                'completed',         // paid and done
                'rejected',          // rejected at any stage
            ])->default('pending');

            $table->string('rejection_reason')->nullable();
            $table->string('rejected_by_role')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};
