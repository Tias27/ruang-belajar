@extends('layouts.guest')

@section('title', 'Masuk - Ruang Belajar')

@section('content')
<section class="screen auth-screen" id="screen-login" aria-labelledby="login-title">
          <aside class="auth-story"><p class="story-kicker"><span data-icon="book-open"></span>Ruang belajarmu</p><h2>Balik lagi?<br>Lanjut belajarnya, yuk</h2><p>Materi, catatan, dan hasil belajarmu tetap di tempatnya. Tinggal lanjut.</p><div class="garden-scene" data-garden-scene aria-hidden="true"></div>
        <div class="garden-caption">Nggak harus selesai sekaligus.</div>
        <button class="motion-toggle" type="button" data-motion-toggle aria-pressed="false" aria-label="Jeda animasi"><span data-icon="pause"></span></button></aside>
          <div class="auth-form-area"><div class="auth-intro"><p class="eyebrow">Hai, ketemu lagi.</p><h1 id="login-title">Masuk dulu, yuk</h1><p>Materimu sudah nunggu di dalam.</p></div>
        @include('auth._feedback')
        @if ($isLocked)
            <p class="guest-notice guest-lock" data-login-lock="{{ $lockUntil }}" role="status">Terlalu banyak percobaan masuk. Coba lagi dalam <span data-lock-seconds>{{ max(0, (int) $lockUntil - time()) }}</span> detik.</p>
            <noscript><p class="field-hint">Muat ulang halaman setelah waktu tunggu selesai.</p></noscript>
        @endif
        <form id="login-form" method="POST" action="{{ route('login') }}" class="guest-form">
            @csrf
            <div class="guest-field"><label for="login-username">Nama pengguna</label><input id="login-username" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Contoh: Tias123" maxlength="50" required aria-describedby="login-username-error" aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"><span class="field-error" id="login-username-error" @if (!$errors->has('username')) hidden @endif>@error('username'){{ $message }}@enderror</span></div>
            <div class="guest-field"><div class="field-label-row"><label for="login-password">Kata sandi</label><a href="{{ route('password.request') }}">Lupa kata sandi?</a></div>
        <div class="password-field"><input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Kata sandimu" required aria-describedby="login-password-error" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"><button type="button" data-password-toggle="login-password" aria-label="Lihat kata sandi" aria-pressed="false"><span data-icon="eye"></span></button></div>
        <span class="field-error" id="login-password-error" @if (!$errors->has('password')) hidden @endif>@error('password'){{ $message }}@enderror</span></div>
            @include('auth._captcha', ['kind' => 'login'])
            <label class="remember-option"><input type="checkbox" name="remember" value="1" @checked(old('remember'))>Tetap masuk di perangkat ini</label>
            <button class="button button-primary auth-submit" type="submit" @disabled($isLocked)>Masuk<span data-icon="arrow-right"></span></button><p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Bikin akun dulu</a></p>
          </form>
        </div>
        </section>
@endsection
