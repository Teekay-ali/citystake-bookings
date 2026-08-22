<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_item_results', function (Blueprint $table) {
            $table->id();
            // Polymorphic: attaches to a UnitInspection (personal spaces) or a
            // RoundSectionInspection (common / outdoor).
            $table->morphs('inspectable');

            $table->string('item_key');
            // Wording is snapshotted at answer time so history stays truthful even
            // after the template is reworded.
            $table->text('item_label');
            $table->string('section');
            $table->string('category');
            // 1..N for the repeated bedroom items; null for everything else.
            $table->unsignedTinyInteger('bedroom_index')->nullable();

            // null result = not yet answered.
            $table->enum('result', ['pass', 'fail', 'na'])->nullable();
            $table->text('note')->nullable();
            $table->json('photos')->nullable();

            // A failed item can escalate into a maintenance report.
            $table->foreignId('maintenance_report_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            // One answer per item per bedroom instance within an inspection.
            $table->unique(
                ['inspectable_type', 'inspectable_id', 'item_key', 'bedroom_index'],
                'item_result_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_item_results');
    }
};
