<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Marketing emails (promotional offers, deals)
            $table->boolean('email_marketing')->default(true)->after('email_verified_at');

            // Reminders (check-in reminders, etc - optional but recommended)
            $table->boolean('email_reminders')->default(true)->after('email_marketing');

            // Newsletters (monthly updates, new features)
            $table->boolean('email_newsletters')->default(true)->after('email_reminders');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_marketing',
                'email_reminders',
                'email_newsletters',
            ]);
        });
    }
};
