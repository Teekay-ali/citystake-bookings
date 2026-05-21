<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('security_deposit', 'caution_fee');
            $table->renameColumn('security_deposit_refunded', 'caution_fee_refunded');
            $table->renameColumn('security_deposit_refunded_at', 'caution_fee_refunded_at');
            $table->renameColumn('security_deposit_refunded_by', 'caution_fee_refunded_by');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('caution_fee', 'security_deposit');
            $table->renameColumn('caution_fee_refunded', 'security_deposit_refunded');
            $table->renameColumn('caution_fee_refunded_at', 'security_deposit_refunded_at');
            $table->renameColumn('caution_fee_refunded_by', 'security_deposit_refunded_by');
        });
    }
};
