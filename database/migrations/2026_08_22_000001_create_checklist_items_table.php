<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            // Stable slug (e.g. living_room.main_door) — referenced by answers and
            // reporting, so it must never change even if the label is reworded.
            $table->string('key')->unique();

            $table->enum('category', ['personal', 'common', 'outdoor']);
            // Grouping within a category. Personal splits into living_room / kitchen
            // / bedroom; the property categories reuse their own name.
            $table->string('section');
            $table->enum('scope', ['unit', 'property']);

            $table->text('label');
            $table->unsignedSmallInteger('sort_order')->default(0);

            // The three bedroom items repeat once per bedroom in a unit.
            $table->boolean('repeats_per_bedroom')->default(false);
            // Whether a Fail must be evidenced with a photo (enforced in the app).
            $table->boolean('requires_photo_on_fail')->default(true);
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['active', 'scope', 'category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
