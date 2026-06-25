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
        Schema::table('procurement_items', function (Blueprint $table) {
            // When true (default), confirming receipt adds this item to stock.
            // Set false for services, labour, or one-off assets that aren't inventory.
            $table->boolean('track_stock')->default(true)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropColumn('track_stock');
        });
    }
};
