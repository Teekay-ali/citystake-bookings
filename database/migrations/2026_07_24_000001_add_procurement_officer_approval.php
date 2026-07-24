<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a procurement-officer approval step before the accountant.
 * New chain: pending (awaiting officer) → officer_approved → accountant_approved
 *            → ceo_approved → purchased → completed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE procurement_requests MODIFY COLUMN status ENUM(
            'pending',
            'officer_approved',
            'accountant_approved',
            'ceo_approved',
            'purchased',
            'completed',
            'rejected'
        ) NOT NULL DEFAULT 'pending'");

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('officer_approved_by')->nullable()->after('submitted_by')->constrained('users')->nullOnDelete();
            $table->timestamp('officer_approved_at')->nullable()->after('officer_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('officer_approved_by');
            $table->dropColumn('officer_approved_at');
        });

        // Fold any officer-approved rows back to pending before shrinking the enum.
        DB::table('procurement_requests')->where('status', 'officer_approved')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE procurement_requests MODIFY COLUMN status ENUM(
            'pending',
            'accountant_approved',
            'ceo_approved',
            'purchased',
            'completed',
            'rejected'
        ) NOT NULL DEFAULT 'pending'");
    }
};
