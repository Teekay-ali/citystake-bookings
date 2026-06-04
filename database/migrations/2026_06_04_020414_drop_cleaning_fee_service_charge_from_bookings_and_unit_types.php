<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['cleaning_fee', 'service_charge']);
        });

        Schema::table('unit_types', function (Blueprint $table) {
            $table->dropColumn(['cleaning_fee', 'service_charge_percent']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('cleaning_fee', 10, 2)->default(0)->after('subtotal');
            $table->decimal('service_charge', 10, 2)->default(0)->after('cleaning_fee');
        });

        Schema::table('unit_types', function (Blueprint $table) {
            $table->decimal('cleaning_fee', 10, 2)->default(0)->after('base_price_per_night');
            $table->decimal('service_charge_percent', 5, 2)->default(0)->after('cleaning_fee');
        });
    }
};
