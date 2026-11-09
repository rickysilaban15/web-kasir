<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeatureSeeder::class,
            PackageSeeder::class,
        ]);

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
