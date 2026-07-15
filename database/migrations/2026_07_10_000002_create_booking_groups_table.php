<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_groups', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lead_name');
            $table->string('lead_email')->nullable();
            $table->string('lead_phone')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // A booking can belong to a group (multiple units booked together).
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('booking_group_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_group_id');
        });
        Schema::dropIfExists('booking_groups');
    }
};
