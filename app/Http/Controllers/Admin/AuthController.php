<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $pin = $request->input('pin');
        $volunteerPin = '1234';
        $masterPin = env('MASTER_PIN', '9999');

        if ($pin === $masterPin) {
            session(['auth_role' => 'master']);
            return redirect()->route('admin.dashboard')->with('success', 'Berjaya log masuk sebagai Master.');
        } elseif ($pin === $volunteerPin) {
            session(['auth_role' => 'volunteer']);
            return redirect()->route('admin.checkin.index')->with('success', 'Berjaya log masuk sebagai Sukarelawan.');
        }

        return back()->with('error', 'Kod Akses tidak sah.');
    }

    public function logout()
    {
        session()->forget('auth_role');
        return redirect()->route('admin.login')->with('success', 'Berjaya log keluar.');
    }
}
