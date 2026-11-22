<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = Str::lower($request->input('email'));
        $key = 'login|' . $email . '|' . $request->ip();
        $maxAttempts = 5;
        $decaySeconds = 60; // 1 minute

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."])->onlyInput('email');
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            return $this->redirectByRole();
        }

        RateLimiter::hit($key, $decaySeconds);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // By default, public registration is disabled. Enable with
        // ALLOW_PUBLIC_REGISTRATION=true in your .env. Alternatively set
        // REGISTRATION_SECRET to require an invite code and include it in the
        // registration form as `invite_code`.
        $allowPublic = env('ALLOW_PUBLIC_REGISTRATION', false);
        if (!$allowPublic) {
            $request->validate([
                'invite_code' => 'required|string',
            ]);
            $secret = env('REGISTRATION_SECRET');
            if (!$secret || $request->input('invite_code') !== $secret) {
                abort(403, 'Registrasi ditutup atau kode undangan salah.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'pemilik',
            'phone' => $validated['phone'] ?? null,
        ]);

        Auth::login($user);

        return $this->redirectByRole()->with('success', 'Registrasi berhasil.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.user.index');
        }

        if ($user->isPemilik()) {
            return redirect()->route('pemilik.dashboard');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        Auth::logout();
        return redirect()->route('login')->with('error', 'Akun Anda tidak memiliki akses.');
    }
}
