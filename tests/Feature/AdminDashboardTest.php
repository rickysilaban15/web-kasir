<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Package;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_dashboard_renders_metrics_and_can_issue_extend_deactivate_and_revoke_license(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('LARIS Licensing & Admin Console', false);

        // Issue a new PRO license from Admin Dashboard
        $storeResponse = $this->post('/admin/licenses', [
            'package_code' => 'PRO',
            'duration_days' => 365,
            'max_devices' => 3,
            'notes' => 'Cabang Utama Bandung',
        ]);
        $storeResponse->assertRedirect('/admin');

        $license = License::where('notes', 'Cabang Utama Bandung')->first();
        $this->assertNotNull($license);
        $this->assertSame(3, $license->max_devices);
        $this->assertStringStartsWith('LARIS-PRO-', $license->license_key);

        // Extend license duration by +30 days
        $extendResponse = $this->post("/admin/licenses/{$license->id}/extend", [
            'additional_days' => 30,
        ]);
        $extendResponse->assertRedirect('/admin');
        $license->refresh();
        $this->assertSame(395, $license->duration_days);

        // Simulate an active POS device and deactivate its slot from Admin Console
        $activation = LicenseActivation::create([
            'license_id' => $license->id,
            'device_installation_id' => 'DEV-BANDUNG-POS-01',
            'device_name' => 'Tablet Kasir Utama',
            'device_model' => 'Samsung Tab A9',
            'os_version' => 'Android 14',
            'app_version' => '1.0.0',
            'status' => 'active',
            'activated_at' => now(),
            'last_validated_at' => now(),
        ]);

        $deactivateResponse = $this->post("/admin/activations/{$activation->id}/deactivate");
        $deactivateResponse->assertRedirect('/admin');
        $activation->refresh();
        $this->assertSame('deactivated', $activation->status);
        $this->assertNotNull($activation->deactivated_at);

        // Revoke the license
        $revokeResponse = $this->post("/admin/licenses/{$license->id}/revoke", [
            'reason' => 'Pelanggaran ketentuan lisensi',
        ]);
        $revokeResponse->assertRedirect('/admin');

        $license->refresh();
        $this->assertSame('revoked', $license->status);
        $this->assertNotNull($license->revoked_at);

        // Update package limits
        $proPackage = Package::where('code', 'PRO')->firstOrFail();
        $pkgResponse = $this->post("/admin/packages/{$proPackage->id}/update", [
            'max_devices' => 5,
            'default_duration_days' => 365,
        ]);
        $pkgResponse->assertRedirect('/admin');
        $proPackage->refresh();
        $this->assertSame(5, $proPackage->max_devices);

        // Create and suspend a user account
        $merchantUser = User::factory()->create([
            'email' => 'toko.berkah@laris.id',
            'status' => 'active',
        ]);
        $suspendResponse = $this->post("/admin/users/{$merchantUser->id}/toggle-status");
        $suspendResponse->assertRedirect('/admin');
        $merchantUser->refresh();
        $this->assertSame('suspended', $merchantUser->status);
    }
}
