<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            // Overall pass rate (0–100), stored when the inspection is completed.
            $table->unsignedTinyInteger('score')->nullable()->after('overall_result');
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
