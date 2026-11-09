<?php

namespace App\Services\Authentication;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data, ?Request $request = null): array
    {
        $user = User::create([
            'name' => trim((string) $data['name']),
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
            'password' => Hash::make((string) $data['password']),
            'role' => 'user',
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        $deviceName = (string) ($data['device_name'] ?? 'LARIS Mobile Android');
        $token = $user->createToken($deviceName)->plainTextToken;

        $this->auditLogService->record(
            action: 'user.registered',
            actor: $user,
            targetType: User::class,
            targetId: $user->id,
            metadata: ['email' => $user->email],
            request: $request
        );

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string}
     */
    public function login(array $credentials, ?Request $request = null): array
    {
        $email = strtolower(trim((string) $credentials['email']));
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check((string) $credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi yang Anda masukkan salah.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda sedang dinonaktifkan. Silakan hubungi dukungan LARIS.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $deviceName = (string) ($credentials['device_name'] ?? 'LARIS Mobile Android');
        $token = $user->createToken($deviceName)->plainTextToken;

        $this->auditLogService->record(
            action: 'user.logged_in',
            actor: $user,
            targetType: User::class,
            targetId: $user->id,
            metadata: ['device_name' => $deviceName],
            request: $request
        );

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}

