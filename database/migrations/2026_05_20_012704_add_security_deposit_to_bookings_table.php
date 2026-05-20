<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('security_deposit', 10, 2)->default(0)->after('discount_amount');
            $table->boolean('security_deposit_refunded')->default(false)->after('security_deposit');
            $table->timestamp('security_deposit_refunded_at')->nullable()->after('security_deposit_refunded');
            $table->foreignId('security_deposit_refunded_by')->nullable()->constrained('users')->nullOnDelete()->after('security_deposit_refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('security_deposit_refunded_by');
            $table->dropColumn([
                'security_deposit',
                'security_deposit_refunded',
                'security_deposit_refunded_at',
            ]);
        });
    }
};
