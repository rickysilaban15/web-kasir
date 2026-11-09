<?php

namespace App\Services\Licensing;

use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseActivationService
{
    public function __construct(
        private readonly LicenseTokenService $tokenService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Mengaktifkan lisensi pada perangkat dan menerbitkan signed token.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function activate(array $data, ?User $actor = null, ?Request $request = null): array
    {
        $normalizedKey = strtoupper(trim((string) $data['license_key']));
        $deviceInstallationId = trim((string) $data['device_installation_id']);

        return DB::transaction(function () use ($normalizedKey, $deviceInstallationId, $data, $actor, $request) {
            /** @var License|null $license */
            $license = License::with('package.features')
                ->where('license_key', $normalizedKey)
                ->lockForUpdate()
                ->first();

            if (! $license) {
                throw ValidationException::withMessages([
                    'license_key' => ['Kode lisensi tidak ditemukan.'],
                ]);
            }

            if ($license->isRevoked()) {
                throw ValidationException::withMessages([
                    'license_key' => ['Lisensi ini telah dicabut oleh administrator.'],
                ]);
            }

            if ($license->isExpired()) {
                if ($license->status !== 'expired') {
                    $license->update(['status' => 'expired']);
                }
                throw ValidationException::withMessages([
                    'license_key' => ['Masa berlaku lisensi ini telah berakhir.'],
                ]);
            }

            // Periksa apakah perangkat ini sudah pernah diaktifkan pada lisensi ini
            /** @var LicenseActivation|null $existingActivation */
            $existingActivation = LicenseActivation::where('license_id', $license->id)
                ->where('device_installation_id', $deviceInstallationId)
                ->first();

            $activeDeviceCount = LicenseActivation::where('license_id', $license->id)
                ->where('status', 'active')
                ->where('device_installation_id', '!=', $deviceInstallationId)
                ->count();

            if ($activeDeviceCount >= $license->max_devices) {
                throw ValidationException::withMessages([
                    'device_installation_id' => [
                        "Batas maksimum perangkat ({$license->max_devices} perangkat) untuk lisensi ini telah tercapai.",
                    ],
                ]);
            }

            $now = now();
            if ($license->activated_at === null) {
                $expiresAt = $license->expires_at;
                if ($expiresAt === null && $license->duration_days !== null) {
                    $expiresAt = $now->copy()->addDays($license->duration_days);
                }

                $license->update([
                    'status' => 'active',
                    'user_id' => $license->user_id ?? $actor?->id,
                    'activated_at' => $now,
                    'expires_at' => $expiresAt,
                ]);
            } elseif ($license->status === 'inactive') {
                $license->update(['status' => 'active']);
            }

            $activation = LicenseActivation::updateOrCreate(
                [
                    'license_id' => $license->id,
                    'device_installation_id' => $deviceInstallationId,
                ],
                [
                    'device_name' => $data['device_name'] ?? 'Android POS Device',
                    'device_model' => $data['device_model'] ?? null,
                    'os_version' => $data['os_version'] ?? null,
                    'app_version' => $data['app_version'] ?? '1.0.0',
                    'status' => 'active',
                    'activated_at' => $existingActivation?->activated_at ?? $now,
                    'last_validated_at' => $now,
                    'deactivated_at' => null,
                    'last_ip_address' => $request?->ip(),
                ]
            );

            $signed = $this->tokenService->issueSignedToken($license->fresh('package.features'), $deviceInstallationId);

            $this->auditLogService->record(
                action: 'license.activated',
                actor: $actor,
                targetType: License::class,
                targetId: $license->id,
                metadata: [
                    'license_key_masked' => $license->maskedKey(),
                    'package_code' => $license->package?->code,
                    'device_installation_id' => $deviceInstallationId,
                    'device_name' => $activation->device_name,
                ],
                request: $request
            );

            return [
                'license_token' => $signed['token'],
                'public_key' => $signed['public_key'],
                'license' => $signed['payload'],
                'activation' => [
                    'id' => $activation->id,
                    'device_installation_id' => $activation->device_installation_id,
                    'device_name' => $activation->device_name,
                    'status' => $activation->status,
                    'activated_at' => $activation->activated_at?->toIso8601String(),
                    'last_validated_at' => $activation->last_validated_at?->toIso8601String(),
                ],
            ];
        });
    }

    /**
     * Memvalidasi ulang lisensi yang sudah aktif pada perangkat dan memperbarui masa offline grace.
     *
     * @return array<string, mixed>
     */
    public function validateActiveDevice(
        string $licenseKey,
        string $deviceInstallationId,
        ?User $actor = null,
        ?Request $request = null
    ): array {
        $normalizedKey = strtoupper(trim($licenseKey));

        /** @var License|null $license */
        $license = License::with('package.features')
            ->where('license_key', $normalizedKey)
            ->first();

        if (! $license || $license->isRevoked()) {
            throw ValidationException::withMessages([
                'license_key' => ['Lisensi tidak valid atau telah dicabut.'],
            ]);
        }

        if ($license->isExpired()) {
            if ($license->status !== 'expired') {
                $license->update(['status' => 'expired']);
            }
            throw ValidationException::withMessages([
                'license_key' => ['Lisensi telah kedaluwarsa.'],
            ]);
        }

        /** @var LicenseActivation|null $activation */
        $activation = LicenseActivation::where('license_id', $license->id)
            ->where('device_installation_id', trim($deviceInstallationId))
            ->where('status', 'active')
            ->first();

        if (! $activation) {
            throw ValidationException::withMessages([
                'device_installation_id' => ['Perangkat belum terdaftar aktif pada lisensi ini.'],
            ]);
        }

        $activation->update([
            'last_validated_at' => now(),
            'last_ip_address' => $request?->ip(),
        ]);

        $signed = $this->tokenService->issueSignedToken($license, $activation->device_installation_id);

        return [
            'license_token' => $signed['token'],
            'public_key' => $signed['public_key'],
            'license' => $signed['payload'],
        ];
    }

    /**
     * Melepas aktivasi perangkat dari lisensi agar slot dapat digunakan perangkat lain.
     */
    public function deactivateDevice(
        string $licenseKey,
        string $deviceInstallationId,
        ?User $actor = null,
        ?Request $request = null
    ): void {
        $normalizedKey = strtoupper(trim($licenseKey));

        /** @var License|null $license */
        $license = License::where('license_key', $normalizedKey)->first();

        if (! $license) {
            throw ValidationException::withMessages([
                'license_key' => ['Kode lisensi tidak ditemukan.'],
            ]);
        }

        /** @var LicenseActivation|null $activation */
        $activation = LicenseActivation::where('license_id', $license->id)
            ->where('device_installation_id', trim($deviceInstallationId))
            ->where('status', 'active')
            ->first();

        if (! $activation) {
            throw ValidationException::withMessages([
                'device_installation_id' => ['Perangkat aktif tidak ditemukan pada lisensi ini.'],
            ]);
        }

        $activation->update([
            'status' => 'deactivated',
            'deactivated_at' => now(),
        ]);

        $this->auditLogService->record(
            action: 'license.device_deactivated',
            actor: $actor,
            targetType: License::class,
            targetId: $license->id,
            metadata: [
                'device_installation_id' => $deviceInstallationId,
            ],
            request: $request
        );
    }
}

