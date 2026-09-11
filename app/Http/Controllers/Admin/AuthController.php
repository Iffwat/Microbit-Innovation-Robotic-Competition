<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('auth_role')) {
            return redirect()->route('admin.checkin.index');
        }
        return view('admin.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'pin' => 'required|string'
        ]);

        $throttleKey = 'pin_login:' . $request->ip();

        // 1. Check Rate Limiter (Brute-force protection: max 5 attempts per 60 seconds)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', __('Terlalu banyak percubaan tidak sah. Sila tunggu :seconds saat.', ['seconds' => $seconds]));
        }

        $inputPin = (string) $request->input('pin');
        $masterPin = config('auth.pins.master') ?? (app()->environment('local') ? '9999' : null);
        $volunteerPin = config('auth.pins.volunteer') ?? (app()->environment('local') ? '1234' : null);

        // 2. Check Master Admin PIN
        if ($this->verifyPin($inputPin, $masterPin)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            session(['auth_role' => 'master']);
            return redirect()->route('admin.dashboard')->with('success', __('Berjaya log masuk sebagai Master Admin.'));
        }

        // 3. Check Volunteer PIN
        if ($this->verifyPin($inputPin, $volunteerPin)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            session(['auth_role' => 'volunteer']);
            return redirect()->route('admin.checkin.index')->with('success', __('Berjaya log masuk sebagai Sukarelawan.'));
        }

        // 4. Record failed attempt
        RateLimiter::hit($throttleKey, 60);

        return back()->with('error', __('Kod Akses tidak sah.'));
    }

    public function logout(Request $request)
    {
        session()->forget('auth_role');
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', __('Berjaya log keluar.'));
    }

    /**
     * Safely verifies input PIN against target PIN.
     * Supports both plain text (using timing-safe hash_equals)
     * and Bcrypt hashes (if stored hashed in .env).
     */
    protected function verifyPin(string $inputPin, ?string $targetPin): bool
    {
        if (empty($targetPin)) {
            return false;
        }

        // Support Bcrypt hashed PIN (e.g. $2y$...)
        if (str_starts_with($targetPin, '$2y$') || str_starts_with($targetPin, '$2a$')) {
            return Hash::check($inputPin, $targetPin);
        }

        // Timing-attack safe comparison for plain text PIN
        return hash_equals((string) $targetPin, (string) $inputPin);
    }
}
