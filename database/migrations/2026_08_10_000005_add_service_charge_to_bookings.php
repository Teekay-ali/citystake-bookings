<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A discretionary service charge for long-stay (USD contract) bookings. It is
 * INCLUDED in the quoted price (price_usd), not added on top — stored purely so
 * it can be disclosed as a line on the invoice. Held in the contract currency
 * (USD). Null for regular bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('service_charge', 12, 2)->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('service_charge');
        });
    }
};
