<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MathCaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_SECONDS = 60;
    const ATTEMPTS_TTL = 7200; // 2 jam counter bertahan
    const LOCK_TTL = 300; // 5 menit — lebih panjang dari lock duration biar bisa deteksi "baru expired"

    public function create()
    {
        $captcha = MathCaptchaService::getQuestion();

        // Cek lockout by IP tiap kali halaman login dibuka
        $lockUntil = Cache::get('login:lock:'.request()->ip());
        $isLocked = $lockUntil !== null && ((int) $lockUntil - time()) > 0;

        return view('auth.login', compact('captcha', 'lockUntil', 'isLocked'));
    }

    public function store(Request $request)
    {
        // Anti array/type-juggling: pastikan username & password string sebelum diproses
        $request->merge([
            'username' => is_string($request->input('username'))
                ? Str::of($request->input('username'))->squish()->lower()->toString()
                : '',
        ]);

        $ip = $request->ip();
        $attemptsKey = 'login:attempts:'.$ip;
        $lockKey = 'login:lock:'.$ip;

        // Cek lockout
        $lockUntil = Cache::get($lockKey);
        if ($lockUntil !== null) {
            $seconds = max(0, (int) $lockUntil - time());
            if ($seconds > 0) {
                // Masih lock — redirect ke login, alert countdown dari GET
                return redirect()->route('login')
                    ->withInput($request->except('password', 'math_captcha'));
            }
            // Lock expired → reset counter, hapus lock
            Cache::forget($lockKey);
            Cache::forget($attemptsKey);
        }

        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'math_captcha' => ['required', 'integer'],
        ], [
            'username.required' => 'Nama pengguna wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'math_captcha.required' => 'Jawaban hitungan wajib diisi.',
            'math_captcha.integer' => 'Jawaban harus berupa angka.',
        ]);

        // Validate math captcha — SALAH CAPTCHA GA KENA RATE LIMIT
        // Pertahankan username + password, cuma captcha yg reset
        if (!MathCaptchaService::validate((int) $credentials['math_captcha'])) {
            return redirect()->route('login')
                ->withInput($request->all())
                ->withErrors(['math_captcha' => 'Jawaban hitungan salah. Coba lagi.']);
        }

        unset($credentials['math_captcha']);
        $credentials['username'] = Str::of((string) $credentials['username'])->squish()->lower()->toString();

        // AUTH ATTEMPT
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, self::ATTEMPTS_TTL);

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::put($lockKey, time() + self::LOCKOUT_SECONDS, self::LOCK_TTL);
                return redirect()->route('login')
                    ->withInput($request->except('password', 'math_captcha'))
                    ->withErrors(['username' => 'Nama pengguna atau kata sandi tidak sesuai. Akun terkunci sementara, coba lagi 1 menit.']);
            }

            $left = self::MAX_ATTEMPTS - $attempts;
            return redirect()->route('login')
                ->withInput($request->except('password', 'math_captcha'))
                ->withErrors(['username' => "Nama pengguna atau kata sandi tidak sesuai. {$left} kali lagi percobaan."]);
        }

        // Login sukses → reset semua
        Cache::forget($attemptsKey);
        Cache::forget($lockKey);

        $request->session()->regenerate();

        $user = $request->user();
        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('student.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}