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
        Schema::create('staff_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->enum('type', [
                'misconduct',
                'attendance',
                'performance',
                'insubordination',
                'policy_violation',
                'other',
            ])->default('other');
            $table->enum('status', ['open', 'responded', 'closed'])->default('open');
            $table->text('staff_response')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_queries');
    }
};
