<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item prices are now optional at submission — the Procurement Officer sets
 * them during review. A null unit_price means "pricing pending" (distinct
 * from a genuine ₦0), and blocks officer approval until filled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->change();
            $table->decimal('total_price', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Backfill nulls before restoring NOT NULL so the change can't fail.
        \DB::table('procurement_items')->whereNull('unit_price')->update(['unit_price' => 0]);
        \DB::table('procurement_items')->whereNull('total_price')->update(['total_price' => 0]);

        Schema::table('procurement_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable(false)->change();
            $table->decimal('total_price', 12, 2)->nullable(false)->change();
        });
    }
};
