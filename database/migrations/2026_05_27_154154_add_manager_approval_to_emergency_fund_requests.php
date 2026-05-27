<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_fund_requests', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'manager_approved',
                'approved',
                'declined',
                'paid'
            ])->default('pending')->change();
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_by');
            $table->text('manager_comment')->nullable()->after('manager_approved_by');
            $table->timestamp('manager_approved_at')->nullable()->after('manager_comment');
        });
    }

    public function down(): void
    {
        Schema::table('emergency_fund_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_approved_by');
            $table->dropColumn(['manager_comment', 'manager_approved_at']);
            $table->enum('status', ['pending', 'approved', 'declined', 'paid'])->default('pending')->change();
        });
    }
};
