<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $request->merge([
            'username' => Str::of((string) $request->input('username'))->squish()->lower()->toString(),
        ]);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[\pL\pN ._-]+$/u', 'unique:users,username,'.$user->id],
            'program_studi' => ['nullable', 'string', 'max:255'],
        ], [
            'username.required' => 'Nama pengguna wajib diisi.',
            'username.regex' => 'Nama pengguna boleh memakai huruf, angka, spasi, titik, garis bawah, atau tanda hubung.',
            'username.min' => 'Nama pengguna minimal 3 karakter.',
            'username.unique' => 'Nama pengguna ini sudah dipakai.',
        ]);

        $data['name'] = $data['username'];

        $user->update($data);

        return back()->with('status', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.min' => 'Kata sandi baru minimal :min karakter.',
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
