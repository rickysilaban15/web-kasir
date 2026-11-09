<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Authentication\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->accountService->register($validated, $request);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi akun berhasil.',
            'data' => [
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => $this->transformUser($result['user']),
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->accountService->login($validated, $request);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'access_token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => $this->transformUser($result['user']),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('licenses.package');

        return response()->json([
            'success' => true,
            'message' => 'Profil akun berhasil diambil.',
            'data' => [
                'user' => $this->transformUser($user),
                'licenses' => $user->licenses->map(fn ($license) => [
                    'id' => $license->id,
                    'license_key_masked' => $license->maskedKey(),
                    'package_code' => $license->package?->code,
                    'status' => $license->status,
                    'max_devices' => $license->max_devices,
                    'expires_at' => $license->expires_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    /**
     * @param  \App\Models\User  $user
     * @return array<string, mixed>
     */
    private function transformUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
        ];
    }
}

