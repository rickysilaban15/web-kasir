<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeatureSeeder::class,
            PackageSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@laris.id'],
            [
                'name' => 'Administrator LARIS',
                'phone' => '081200000000',
                'password' => Hash::make('AdminLaris#2026'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        AppVersion::updateOrCreate(
            ['platform' => 'android', 'version_code' => 1],
            [
                'version_name' => '1.0.0',
                'min_supported_version_code' => 1,
                'is_force_update' => false,
                'release_notes' => 'Rilis awal fondasi LARIS POS Offline-First.',
                'published_at' => now(),
            ]
        );
    }
}
