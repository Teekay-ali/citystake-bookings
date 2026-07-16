<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            // When true (default), a 1-night stay's caution fee is the room's
            // nightly rate; when false, the flat property caution fee is used.
            $table->boolean('one_night_caution_uses_rate')->default(true)->after('caution_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('one_night_caution_uses_rate');
        });
    }
};
