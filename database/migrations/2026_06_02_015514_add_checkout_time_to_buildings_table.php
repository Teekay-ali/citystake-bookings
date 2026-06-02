<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->time('standard_checkout_time')->default('12:00:00')->after('caution_fee_amount');
            $table->decimal('late_checkout_fee_per_hour', 10, 2)->default(10000)->after('standard_checkout_time');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['standard_checkout_time', 'late_checkout_fee_per_hour']);
        });
    }
};
