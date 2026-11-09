<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\Licensing\LicenseActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseActivationService $activationService
    ) {}

    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'device_installation_id' => ['required', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'device_model' => ['nullable', 'string', 'max:120'],
            'os_version' => ['nullable', 'string', 'max:64'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $result = $this->activationService->activate(
            $validated,
            $request->user('sanctum'),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan.',
            'data' => $result,
        ]);
    }

    public function validateLicense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'device_installation_id' => ['required', 'string', 'max:100'],
        ]);

        $result = $this->activationService->validateActiveDevice(
            $validated['license_key'],
            $validated['device_installation_id'],
            $request->user('sanctum'),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Validasi lisensi berhasil.',
            'data' => $result,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
        ]);

        $license = License::with(['package.features', 'activeActivations'])
            ->where('license_key', strtoupper(trim($validated['license_key'])))
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Status lisensi ditemukan.',
            'data' => [
                'license_key_masked' => $license->maskedKey(),
                'package_code' => $license->package?->code,
                'package_name' => $license->package?->name,
                'status' => $license->isExpired() ? 'expired' : $license->status,
                'max_devices' => $license->max_devices,
                'active_devices_count' => $license->activeActivations->count(),
                'expires_at' => $license->expires_at?->toIso8601String(),
                'entitlements' => $license->package?->features->pluck('code')->values() ?? [],
            ],
        ]);
    }

    public function deactivateDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'device_installation_id' => ['required', 'string', 'max:100'],
        ]);

        $this->activationService->deactivateDevice(
            $validated['license_key'],
            $validated['device_installation_id'],
            $request->user('sanctum'),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil dilepas dari lisensi.',
        ]);
    }
}

