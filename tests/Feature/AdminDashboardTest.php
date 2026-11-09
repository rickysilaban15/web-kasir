<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Feature;
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
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);
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

        $this->post("/admin/users/{$admin->id}/toggle-status")
            ->assertSessionHasErrors('user');
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_admin_routes_require_an_active_admin_session(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/licenses', [
            'package_code' => 'PRO',
            'duration_days' => 30,
        ])->assertRedirect('/admin/login');

        $merchant = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($merchant);
        $this->get('/admin')->assertForbidden();
        $this->post('/admin/licenses', [
            'package_code' => 'PRO',
            'duration_days' => 30,
        ])->assertForbidden();

        $suspended = User::factory()->create(['role' => 'admin', 'status' => 'suspended']);
        $this->actingAs($suspended);
        $this->get('/admin')->assertForbidden();
    }

    public function test_admin_can_login_and_logout_with_session(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin.test@laris.id',
            'password' => 'SandiAdminKuat#2026',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->get('/admin/login')->assertOk()->assertSee('Masuk admin');
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'salah',
        ])->assertSessionHasErrors('email');
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'SandiAdminKuat#2026',
        ])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();

        $legacy = User::factory()->create([
            'email' => 'admin.legacy@laris.id',
            'password' => 'AdminLaris#2026',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->post('/admin/login', [
            'email' => $legacy->email,
            'password' => 'AdminLaris#2026',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_update_package_features_and_change_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);
        $package = Package::where('code', 'PRO')->firstOrFail();
        $feature = Feature::where('code', 'profit_analysis')->firstOrFail();

        $this->post("/admin/packages/{$package->id}/features", [
            'feature_ids' => [$feature->id],
        ])->assertRedirect('/admin');
        $this->assertEqualsCanonicalizing([$feature->id], $package->features()->pluck('features.id')->all());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'package.features_updated',
            'actor_id' => $admin->id,
            'target_id' => (string) $package->id,
        ]);

        $this->post("/admin/packages/{$package->id}/features", [
            'feature_ids' => [999999],
        ])->assertSessionHasErrors('feature_ids.0');
        $this->assertEqualsCanonicalizing([$feature->id], $package->features()->pluck('features.id')->all());
    }
}
