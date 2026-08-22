<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_turnovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', [
                'cleaning_in_progress', 'cleaning_completed',
                'qa_in_progress', 'ready', 'blocked', 'cancelled',
            ])->default('cleaning_in_progress');

            // Hand-off timestamps + actors — the basis for turnaround analytics.
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cleaning_requested_at')->nullable();
            $table->foreignId('cleaning_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleaning_completed_at')->nullable();
            $table->foreignId('cleaning_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qa_started_at')->nullable();
            $table->timestamp('qa_completed_at')->nullable();
            $table->timestamp('ready_at')->nullable();

            $table->foreignId('unit_inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blocked_date_id')->nullable()->constrained('blocked_dates')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status']);
            $table->index(['building_id', 'status']);
            $table->index('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_turnovers');
    }
};
