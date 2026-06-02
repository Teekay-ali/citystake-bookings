<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete()->after('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_out_by');
            $table->dropColumn('checked_out_at');
        });
    }
};
