<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Image;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user - credentials come from .env, never hardcoded
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $name     = env('ADMIN_NAME', 'Admin');

        if ($email && $password) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'              => $name,
                    'password'          => bcrypt($password),
                    'is_admin'          => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->call(SixtyNinePlaceSeeder::class);

        $this->call(RolesAndPermissionsSeeder::class);
    }

}
