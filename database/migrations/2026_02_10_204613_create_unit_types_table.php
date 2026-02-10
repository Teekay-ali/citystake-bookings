<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('bedroom_type', ['2-bed', '3-bed', '4-bed']);
            $table->integer('max_guests');
            $table->decimal('base_price_per_night', 10, 2);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('service_charge_percent', 5, 2)->default(10);
            $table->text('description')->nullable();
            $table->json('specific_amenities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['building_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
