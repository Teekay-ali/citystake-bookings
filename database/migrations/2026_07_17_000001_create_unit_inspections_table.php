<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('in_progress');
            // Overall verdict, set when the inspection is worked on/completed.
            $table->enum('overall_result', ['ok', 'concerns'])->nullable();

            $table->date('scheduled_for')->nullable();   // reserved for future routine scheduling
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('summary')->nullable();
            $table->json('photos')->nullable();           // inspection-level photo paths

            $table->timestamps();

            $table->index(['building_id', 'status']);
            $table->index(['unit_id', 'status']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inspections');
    }
};
