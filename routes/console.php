<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\Licensing\LicenseTokenService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('laris:admin {email?}', function (): void {
    $email = strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email admin'))));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email admin tidak valid.');
        return;
    }

    $password = (string) $this->secret('Kata sandi admin baru (minimal 12 karakter)');
    if (strlen($password) < 12 || $password !== (string) $this->secret('Ulangi kata sandi')) {
        $this->error('Kata sandi tidak cocok atau kurang dari 12 karakter.');
        return;
    }

    User::updateOrCreate(
        ['email' => $email],
        [
            'name' => 'Administrator LARIS',
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]
    );
    $this->info("Admin {$email} siap digunakan.\n");
})->purpose('Buat atau ganti kata sandi administrator LARIS');

Artisan::command('laris:license-public-key', function (LicenseTokenService $tokens): void {
    $this->line($tokens->publicKeyBase64());
})->purpose('Tampilkan kunci publik lisensi untuk pinning build Flutter');
