<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $authenticated = Auth::attempt([
            'email' => strtolower(trim($credentials['email'])),
            'password' => $credentials['password'],
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi admin tidak valid.',
            ]);
        }

        // Akun yang pernah dibuat seeder lama memakai kata sandi publik.
        // Wajib rotasi lewat `php artisan laris:admin` sebelum akses web.
        if (Hash::check('AdminLaris#2026', $request->user()->password)) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Kata sandi admin lama harus diganti lewat perintah php artisan laris:admin.',
            ]);
        }

        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);
        $auditLogService->record('admin.logged_in', $request->user(), request: $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $auditLogService->record('admin.logged_out', $request->user(), request: $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
