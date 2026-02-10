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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('bedroom_type', ['2-bed', '3-bed', '4-bed']);
            $table->integer('max_guests');
            $table->string('address');
            $table->string('city')->default('Lagos');
            $table->decimal('base_price_per_night', 10, 2);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('service_charge_percent', 5, 2)->default(10);
            $table->boolean('is_active')->default(true);
            $table->json('amenities')->nullable();
            $table->json('house_rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
