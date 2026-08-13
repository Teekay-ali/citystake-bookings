<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A procurement request can be a continuation of a previously completed one —
 * a self-reference for traceability. Null for standalone requests; if the
 * linked request is ever deleted the link simply clears.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->foreignId('related_request_id')->nullable()->after('building_id')
                ->constrained('procurement_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_request_id');
        });
    }
};
