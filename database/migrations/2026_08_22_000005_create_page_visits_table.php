<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route_name')->nullable();   // Ziggy/Laravel route name, for grouping
            $table->string('path', 512);                 // the visited URL path
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('visited_at')->index();
            // No updated_at/created_at pair needed — visited_at is the record time.

            $table->index(['user_id', 'visited_at']);
            $table->index('route_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
