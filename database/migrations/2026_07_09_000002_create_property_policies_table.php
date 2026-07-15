<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('body'); // sanitized rich-text HTML
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['building_id', 'version']);
        });

        // Snapshot the policy version that applied to each booking at creation.
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('policy_version')->nullable()->after('special_requests');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('policy_version');
        });
        Schema::dropIfExists('property_policies');
    }
};
