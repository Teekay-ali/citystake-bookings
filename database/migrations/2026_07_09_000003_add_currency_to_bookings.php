<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Currency the booking was contracted in. NGN is the default; USD is used
            // for long (typically annual) stays. All financials are still recorded in
            // NGN using the rate locked at creation.
            $table->string('currency', 3)->default('NGN')->after('total_amount');
            $table->decimal('price_usd', 12, 2)->nullable()->after('currency');      // contracted USD amount
            $table->decimal('exchange_rate', 12, 2)->nullable()->after('price_usd');  // NGN per 1 USD, locked
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['currency', 'price_usd', 'exchange_rate']);
        });
    }
};
