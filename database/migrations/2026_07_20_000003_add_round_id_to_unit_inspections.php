<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->foreignId('inspection_round_id')->nullable()->after('building_id')
                ->constrained('inspection_rounds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspection_round_id');
        });
    }
};
