@extends('layouts.guest')

@section('title', 'Daftar - Ruang Belajar')

@section('content')
<section class="screen auth-screen" id="screen-register" aria-labelledby="register-title">
          <aside class="auth-story register-story"><p class="story-kicker"><span data-icon="book-open"></span>Mulai dari materi yang kamu punya</p><h2>Biar materinya<br>nggak cuma<br>numpuk di folder</h2><p>Simpan di satu tempat, pahami isinya, lalu belajar dengan caramu.</p><div class="garden-scene" data-garden-scene aria-hidden="true"></div>
        <div class="garden-caption">Satu langkah dulu. Sisanya menyusul.</div>
        <button class="motion-toggle" type="button" data-motion-toggle aria-pressed="false" aria-label="Jeda animasi"><span data-icon="pause"></span></button></aside>
          <div class="auth-form-area"><div class="auth-intro"><p class="eyebrow">Belum kenalan? Yuk, mulai.</p><h1 id="register-title">Bikin ruang belajarmu</h1><p>Isi sedikit, lalu bawa materi pertamamu.</p></div>
        @include('auth._feedback')
        <form id="register-form" method="POST" action="{{ route('register') }}" class="guest-form">
            @csrf
            <div class="guest-field"><label for="register-email">Email</label><input id="register-email" name="email" value="{{ old('email') }}" type="email" autocomplete="email" placeholder="nama@email.com" maxlength="255" required aria-describedby="register-email-error" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"><span class="field-error" id="register-email-error" @if (!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</span></div>
            <div class="guest-field"><label for="register-username">Nama pengguna</label><input id="register-username" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Contoh: Tias123" minlength="3" maxlength="50" required aria-describedby="register-username-hint register-username-error" aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"><span class="field-hint" id="register-username-hint">Nama ini yang kamu pakai buat masuk nanti.</span><span class="field-error" id="register-username-error" @if (!$errors->has('username')) hidden @endif>@error('username'){{ $message }}@enderror</span></div>
            <div class="guest-field"><label for="register-password">Kata sandi</label><div class="password-field"><input id="register-password" name="password" type="password" autocomplete="new-password" placeholder="Minimal 8 karakter" minlength="8" required aria-describedby="register-password-hint register-password-error" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"><button type="button" data-password-toggle="register-password" aria-label="Lihat kata sandi" aria-pressed="false"><span data-icon="eye"></span></button></div>
        <span class="field-hint" id="register-password-hint">Minimal 8 karakter.</span><span class="field-error" id="register-password-error" @if (!$errors->has('password')) hidden @endif>@error('password'){{ $message }}@enderror</span></div>
            <div class="guest-field"><label for="register-confirm">Ulangi kata sandi</label><div class="password-field"><input id="register-confirm" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Ketik sekali lagi" required aria-describedby="register-confirm-error" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"><button type="button" data-password-toggle="register-confirm" aria-label="Lihat kata sandi" aria-pressed="false"><span data-icon="eye"></span></button></div>
        <span class="field-error" id="register-confirm-error" @if (!$errors->has('password_confirmation')) hidden @endif>@error('password_confirmation'){{ $message }}@enderror</span></div>
            @include('auth._captcha', ['kind' => 'register'])
            <button class="button button-primary auth-submit" type="submit">Bikin akun<span data-icon="arrow-right"></span></button><p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk aja</a></p>
          </form>
        </div>
        </section>
@endsection
