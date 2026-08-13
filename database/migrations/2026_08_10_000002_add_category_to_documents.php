<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a documentable separate kinds of attachments — e.g. a procurement
 * request's optional supporting files ('attachment') from its mandatory
 * purchase receipt ('receipt'). Null keeps existing documents uncategorised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('category')->nullable()->after('documentable_type');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
