<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('round_section_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();

            // Property-level checklist sections, inspected once per round.
            $table->enum('section', ['common', 'outdoor']);

            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            // Derived from item results when the section is completed.
            $table->enum('result', ['pass', 'fail'])->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // One common + one outdoor section per round.
            $table->unique(['inspection_round_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_section_inspections');
    }
};
