<?php

namespace App\Services\Licensing;

use App\Models\License;
use App\Models\Package;

class EntitlementService
{
    /**
     * Mengembalikan daftar kode fitur (entitlements) untuk paket tertentu.
     *
     * @return list<string>
     */
    public function getFeatureCodesForPackage(Package $package): array
    {
        return $package->features()
            ->orderBy('code')
            ->pluck('code')
            ->values()
            ->all();
    }

    /**
     * Mengembalikan daftar kode fitur (entitlements) untuk lisensi tertentu.
     *
     * @return list<string>
     */
    public function getFeatureCodesForLicense(License $license): array
    {
        $license->loadMissing('package.features');

        if (! $license->package || $license->isRevoked() || $license->isExpired()) {
            return [];
        }

        return $license->package->features
            ->pluck('code')
            ->values()
            ->all();
    }
}

