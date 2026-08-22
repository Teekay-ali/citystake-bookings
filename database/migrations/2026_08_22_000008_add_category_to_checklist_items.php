<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            // Issue type for recurring-failure analytics (plumbing, electrical, …).
            // Distinct from the existing `category` column (personal/common/outdoor scope).
            $table->string('issue_category')->nullable()->after('section');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn('issue_category');
        });
    }
};
