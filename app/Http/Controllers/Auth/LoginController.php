<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\AuditLogger;

class LoginController extends Controller
{
    /**
     * Menampilkan halaman form login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Memproses proses login menggunakan username atau email.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        // Cek login via username terlebih dahulu, jika tidak ditemukan coba via email
        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            // Log successful login
            AuditLogger::login($credentials['username'], 'success');

            return redirect()->intended('/dashboard')->with('status', 'Selamat datang kembali!');
        }

        // Log failed login attempt
        AuditLogger::login($credentials['username'], 'failed');

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->onlyInput('username');
    }

    /**
     * Logout dan mengakhiri sesi pengguna.
     */
    public function logout(Request $request)
    {
        // Log logout before destroying session
        AuditLogger::logout();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
