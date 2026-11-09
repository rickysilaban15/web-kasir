<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique(); // FREE, PRO, BUSINESS
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_devices')->default(1);
            $table->unsignedInteger('default_duration_days')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('module', 64)->default('general')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('package_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['package_id', 'feature_id']);
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('status', 24)->default('inactive')->index(); // inactive | active | expired | revoked
            $table->unsignedInteger('max_devices')->default(1);
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('offline_grace_days')->default(7);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->string('device_installation_id', 100)->index();
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version', 64)->nullable();
            $table->string('app_version', 32)->nullable();
            $table->string('status', 24)->default('active')->index(); // active | deactivated | revoked
            $table->timestamp('activated_at');
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'device_installation_id'], 'uq_license_device_installation');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('target_type', 100)->nullable()->index();
            $table->string('target_id', 100)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 24)->default('android')->index();
            $table->string('version_name', 32);
            $table->unsignedInteger('version_code');
            $table->unsignedInteger('min_supported_version_code')->default(1);
            $table->boolean('is_force_update')->default(false);
            $table->text('release_notes')->nullable();
            $table->string('download_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('license_activations');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('package_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('personal_access_tokens');
    }
};

