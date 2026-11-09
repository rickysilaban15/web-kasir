<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function config(): JsonResponse
    {
        $packages = Package::with('features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Package $package) => [
                'code' => $package->code,
                'name' => $package->name,
                'description' => $package->description,
                'max_devices' => $package->max_devices,
                'features' => $package->features->pluck('code')->values(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi aplikasi berhasil diambil.',
            'data' => [
                'app_name' => 'LARIS',
                'offline_grace_days' => config('licensing.default_offline_grace_days', 7),
                'packages' => $packages,
            ],
        ]);
    }

    public function version(Request $request): JsonResponse
    {
        $platform = strtolower((string) $request->query('platform', 'android'));

        $latest = AppVersion::where('platform', $platform)
            ->orderByDesc('version_code')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Informasi versi aplikasi.',
            'data' => $latest ? [
                'platform' => $latest->platform,
                'version_name' => $latest->version_name,
                'version_code' => $latest->version_code,
                'min_supported_version_code' => $latest->min_supported_version_code,
                'is_force_update' => $latest->is_force_update,
                'release_notes' => $latest->release_notes,
            ] : [
                'platform' => $platform,
                'version_name' => '1.0.0',
                'version_code' => 1,
                'min_supported_version_code' => 1,
                'is_force_update' => false,
                'release_notes' => 'Rilis awal LARIS POS.',
            ],
        ]);
    }
}

