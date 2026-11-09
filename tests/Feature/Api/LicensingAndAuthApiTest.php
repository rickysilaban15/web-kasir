<?php

namespace Tests\Feature\Api;

use App\Models\License;
use App\Models\Package;
use App\Services\Licensing\LicenseTokenService;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicensingAndAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            FeatureSeeder::class,
            PackageSeeder::class,
        ]);
    }

    public function test_user_can_register_login_and_fetch_profile(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Pemilik Warung Maju',
            'email' => 'owner@warungmaju.id',
            'phone' => '081234567890',
            'password' => 'Rahasia#2026',
            'password_confirmation' => 'Rahasia#2026',
            'device_name' => 'Galaxy Tab Kasir 1',
        ]);

        $registerResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'owner@warungmaju.id');

        $token = $registerResponse->json('data.access_token');
        $this->assertNotEmpty($token);

        $meResponse = $this->withToken($token)->getJson('/api/v1/me');
        $meResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.name', 'Pemilik Warung Maju');
    }

    public function test_license_activation_issues_valid_ed25519_signed_token_and_enforces_device_limit(): void
    {
        $proPackage = Package::where('code', 'PRO')->firstOrFail();

        $license = License::create([
            'license_key' => 'LARIS-PRO1-2026-ABCD',
            'package_id' => $proPackage->id,
            'status' => 'inactive',
            'max_devices' => 1,
            'duration_days' => 365,
            'offline_grace_days' => 7,
        ]);

        // Aktivasi perangkat pertama berhasil
        $activateResponse = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'LARIS-PRO1-2026-ABCD',
            'device_installation_id' => 'device-uuid-001',
            'device_name' => 'Kasir Utama',
            'device_model' => 'SM-X110',
            'os_version' => 'Android 14',
            'app_version' => '1.0.0',
        ]);

        $activateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.license.package_code', 'PRO');

        $signedToken = $activateResponse->json('data.license_token');
        $publicKey = $activateResponse->json('data.public_key');

        /** @var LicenseTokenService $tokenService */
        $tokenService = app(LicenseTokenService::class);
        $verifiedPayload = $tokenService->verifyToken($signedToken, $publicKey);

        $this->assertNotNull($verifiedPayload);
        $this->assertSame('PRO', $verifiedPayload['package_code']);
        $this->assertSame('device-uuid-001', $verifiedPayload['device_installation_id']);
        $this->assertContains('profit_analysis', $verifiedPayload['entitlements']);

        // Aktivasi perangkat kedua ditolak karena max_devices = 1
        $secondDeviceResponse = $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'LARIS-PRO1-2026-ABCD',
            'device_installation_id' => 'device-uuid-002',
            'device_name' => 'Kasir Kedua',
        ]);

        $secondDeviceResponse->assertStatus(422)
            ->assertJsonValidationErrors(['device_installation_id']);

        // Deaktivasi perangkat pertama membuka kembali slot untuk perangkat kedua
        $this->postJson('/api/v1/licenses/deactivate-device', [
            'license_key' => 'LARIS-PRO1-2026-ABCD',
            'device_installation_id' => 'device-uuid-001',
        ])->assertOk();

        $this->postJson('/api/v1/licenses/activate', [
            'license_key' => 'LARIS-PRO1-2026-ABCD',
            'device_installation_id' => 'device-uuid-002',
            'device_name' => 'Kasir Kedua',
        ])->assertOk();

        $this->assertSame('active', $license->fresh()->status);
    }
}

