<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MathCaptchaService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function create()
    {
        $captcha = \App\Services\MathCaptchaService::getQuestion();
        return view('auth.register', compact('captcha'));
    }

    public function store(Request $request)
    {
        // Anti array/type-juggling
                $request->merge([
                    'username' => is_string($request->input('username'))
                        ? Str::of($request->input('username'))->squish()->lower()->toString()
                        : '',
                ]);

                $data = $request->validate([
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[\\pL\\pN ._-]+$/u', 'unique:users,username'],
                    'password' => ['required', 'confirmed', Password::defaults()],
                    'math_captcha' => ['required', 'integer'],
                ], [
                    'email.required' => 'Email wajib diisi.',
                    'email.email' => 'Format email belum benar.',
                    'email.unique' => 'Email ini sudah terdaftar.',
                    'username.required' => 'Nama pengguna wajib diisi.',
                    'username.regex' => 'Nama pengguna boleh memakai huruf, angka, spasi, titik, garis bawah, atau tanda hubung.',
                    'username.min' => 'Nama pengguna minimal 3 karakter.',
                    'username.unique' => 'Nama pengguna ini sudah dipakai.',
                    'password.required' => 'Kata sandi wajib diisi.',
                    'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
                    'password.min' => 'Kata sandi minimal :min karakter.',
                    'math_captcha.required' => 'Jawaban hitungan wajib diisi.',
                    'math_captcha.integer' => 'Jawaban harus berupa angka.',
                ]);

                // Validate math captcha — pertahankan semua input, cuma captcha yg reset
                if (!MathCaptchaService::validate((int) $data['math_captcha'])) {
                    return redirect()->route('register')
                        ->withInput($request->all())
                        ->withErrors(['math_captcha' => 'Jawaban hitungan salah. Coba lagi.']);
                }

        $user = User::create([
            'name' => $data['username'],
            'username' => $data['username'],
            'email' => $data['email'],
            'program_studi' => '',
            'role' => 'mahasiswa',
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Akun berhasil dibuat. Selamat datang, '.$user->username.'! Kamu sudah bisa upload materi dan mulai belajar.');
    }
}