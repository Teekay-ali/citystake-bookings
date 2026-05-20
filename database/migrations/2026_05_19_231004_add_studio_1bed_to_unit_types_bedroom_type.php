<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE unit_types MODIFY COLUMN bedroom_type ENUM('studio','1-bed','2-bed','3-bed','4-bed') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE unit_types MODIFY COLUMN bedroom_type ENUM('2-bed','3-bed','4-bed') NOT NULL");
    }
};
