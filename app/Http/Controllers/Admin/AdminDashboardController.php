<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('packages')) {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
        } elseif (Package::count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
        }

        $packages = Package::withCount('features')->orderBy('sort_order')->get();
        $users = User::withCount('licenses')->latest()->limit(20)->get();
        $licenses = License::with(['package', 'user', 'activeActivations'])
            ->latest()
            ->limit(25)
            ->get();
        $recentActivations = LicenseActivation::with(['license.package', 'license.user'])
            ->latest('activated_at')
            ->limit(15)
            ->get();
        $recentAuditLogs = AuditLog::with('actor')
            ->latest('created_at')
            ->limit(15)
            ->get();

        $metrics = [
            'total_users' => User::count(),
            'total_licenses' => License::count(),
            'active_licenses' => License::where('status', 'active')->count(),
            'active_devices' => LicenseActivation::where('status', 'active')->count(),
        ];

        return view('admin.dashboard', compact(
            'packages',
            'users',
            'licenses',
            'recentActivations',
            'recentAuditLogs',
            'metrics'
        ));
    }

    public function storeLicense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_code' => ['required', 'string', 'exists:packages,code'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'max_devices' => ['nullable', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $package = Package::where('code', $validated['package_code'])->firstOrFail();
        $segment1 = strtoupper(Str::random(4));
        $segment2 = strtoupper(Str::random(4));
        $licenseKey = "LARIS-{$package->code}-{$segment1}-{$segment2}";

        $license = License::create([
            'license_key' => $licenseKey,
            'package_id' => $package->id,
            'status' => 'unused',
            'max_devices' => $validated['max_devices'] ?? $package->max_devices_default,
            'duration_days' => $validated['duration_days'],
            'offline_grace_days' => 7,
            'notes' => $validated['notes'] ?? '',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'license.issued',
            'target_type' => 'License',
            'target_id' => (string) $license->id,
            'metadata' => [
                'license_key_masked' => $license->maskedKey(),
                'package_code' => $package->code,
                'duration_days' => $license->duration_days,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Lisensi baru berhasil diterbitkan: {$license->license_key}");
    }

    public function revokeLicense(Request $request, License $license): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $license->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $validated['reason'] ?? 'Dicabut oleh Administrator',
        ]);

        $license->activations()->where('status', 'active')->update([
            'status' => 'revoked',
            'deactivated_at' => now(),
        ]);

        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'license.revoked',
            'target_type' => 'License',
            'target_id' => (string) $license->id,
            'metadata' => [
                'license_key_masked' => $license->maskedKey(),
                'reason' => $license->revocation_reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Lisensi {$license->maskedKey()} telah dicabut.");
    }

    public function extendLicense(Request $request, License $license): RedirectResponse
    {
        $validated = $request->validate([
            'additional_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $days = (int) ($validated['additional_days'] ?? 30);
        $baseExpiry = ($license->expires_at !== null && $license->expires_at->isFuture())
            ? $license->expires_at
            : now();

        $nextStatus = $license->status === 'expired' ? 'active' : $license->status;
        $nextExpiry = $license->expires_at !== null || $license->status === 'active' || $license->status === 'expired'
            ? $baseExpiry->copy()->addDays($days)
            : null;

        $license->update([
            'duration_days' => $license->duration_days + $days,
            'expires_at' => $nextExpiry,
            'status' => $nextStatus,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'license.extended',
            'target_type' => 'License',
            'target_id' => (string) $license->id,
            'metadata' => [
                'license_key_masked' => $license->maskedKey(),
                'additional_days' => $days,
                'new_duration_days' => $license->duration_days,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Masa aktif lisensi {$license->maskedKey()} berhasil diperpanjang +{$days} hari.");
    }

    public function deactivateDevice(Request $request, LicenseActivation $activation): RedirectResponse
    {
        $activation->update([
            'status' => 'deactivated',
            'deactivated_at' => now(),
        ]);

        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'device.deactivated_by_admin',
            'target_type' => 'LicenseActivation',
            'target_id' => (string) $activation->id,
            'metadata' => [
                'device_installation_id' => $activation->device_installation_id,
                'device_name' => $activation->device_name,
                'license_id' => $activation->license_id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Perangkat {$activation->device_name} berhasil dinonaktifkan dari slot lisensi.");
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        $nextStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $nextStatus]);

        if ($nextStatus === 'suspended') {
            $user->tokens()->delete();
        }

        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'user.status_changed',
            'target_type' => 'User',
            'target_id' => (string) $user->id,
            'metadata' => [
                'email' => $user->email,
                'new_status' => $nextStatus,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Status akun {$user->email} diubah menjadi {$nextStatus}.");
    }

    public function updatePackageLimits(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'max_devices' => ['required', 'integer', 'min:1', 'max:100'],
            'default_duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $package->update([
            'max_devices' => $validated['max_devices'],
            'default_duration_days' => $validated['default_duration_days'],
        ]);

        AuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'package.updated',
            'target_type' => 'Package',
            'target_id' => (string) $package->id,
            'metadata' => [
                'code' => $package->code,
                'max_devices' => $package->max_devices,
                'default_duration_days' => $package->default_duration_days,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Konfigurasi paket {$package->code} berhasil diperbarui.");
    }
}

