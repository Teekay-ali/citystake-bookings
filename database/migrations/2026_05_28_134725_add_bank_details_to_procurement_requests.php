<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->string('supplier_bank_name')->nullable()->after('supplier_email');
            $table->string('supplier_account_number')->nullable()->after('supplier_bank_name');
            $table->string('supplier_account_name')->nullable()->after('supplier_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropColumn(['supplier_bank_name', 'supplier_account_number', 'supplier_account_name']);
        });
    }
};
