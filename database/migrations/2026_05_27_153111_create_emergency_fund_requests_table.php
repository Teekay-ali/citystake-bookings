<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_fund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->text('urgency_description');
            $table->string('evidence')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined', 'paid'])->default('pending');
            $table->text('ceo_comment')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('month_year', 7); // e.g. "2026-05"
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Add monthly_emergency_limit to buildings
        Schema::table('buildings', function (Blueprint $table) {
            $table->decimal('monthly_emergency_limit', 12, 2)->default(200000)->after('caution_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_fund_requests');
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('monthly_emergency_limit');
        });
    }
};
