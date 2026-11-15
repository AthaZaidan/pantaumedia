<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login SOSPEM.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $this->ensureDefaultAdminExists();

        return view('auth.login');
    }

    /**
     * Proses autentikasi login pengguna.
     */
    public function login(Request $request): RedirectResponse
    {
        $this->ensureDefaultAdminExists();

        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginIdentifier = trim((string) $credentials['email']);
        $remember = $request->boolean('remember');

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($loginIdentifier)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($loginIdentifier)])
            ->first();

        if ($user !== null && Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Username atau kata sandi yang Anda masukkan tidak sesuai.'])
            ->withInput($request->only('email'));
    }

    /**
     * Proses keluar (logout) dan hapus sesi pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Pastikan akun admin awal tersedia ketika tabel users masih kosong
     * dan selaraskan akun default lama (Admin SOSPEM) ke kredensial Admin.
     */
    private function ensureDefaultAdminExists(): void
    {
        $adminEmail = (string) env('ADMIN_EMAIL', 'admin@sospem.id');
        $adminName = (string) env('ADMIN_NAME', 'Admin');
        $adminPassword = (string) env('ADMIN_PASSWORD', 'admin');

        $legacyAdmin = User::where('email', $adminEmail)
            ->where('name', 'Admin SOSPEM')
            ->first();

        if ($legacyAdmin !== null) {
            $legacyAdmin->update([
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
            ]);

            return;
        }

        if (User::count() > 0) {
            return;
        }

        User::create([
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
        ]);
    }
}
