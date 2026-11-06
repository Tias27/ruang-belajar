@extends('layouts.guest')

@section('title', 'Atur ulang kata sandi - Ruang Belajar')

@section('content')
<section class="screen forgot-screen" id="screen-reset" aria-labelledby="reset-title">
    <div class="forgot-icon" data-icon="mail"></div>
    <p class="eyebrow">Sebentar lagi bisa lanjut belajar.</p>
    <h1 id="reset-title">Bikin kata sandi baru</h1>
    <p class="subtitle">Pilih kata sandi yang mudah kamu ingat.</p>
    @include('auth._feedback')
    <form id="reset-form" method="POST" action="{{ route('password.store') }}" class="guest-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="guest-field">
            <label for="reset-email">Email akunmu</label>
            <input id="reset-email" name="email" type="email" value="{{ old('email', $request->email) }}" autocomplete="email" required aria-describedby="reset-email-error" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
            <span class="field-error" id="reset-email-error" @if (!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</span>
        </div>
        <div class="guest-field">
            <label for="reset-password">Kata sandi baru</label>
            <div class="password-field">
                <input id="reset-password" name="password" type="password" autocomplete="new-password" placeholder="Minimal 8 karakter" minlength="8" required aria-describedby="reset-password-error" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                <button type="button" data-password-toggle="reset-password" aria-label="Lihat kata sandi" aria-pressed="false"><span data-icon="eye"></span></button>
            </div>
            <span class="field-error" id="reset-password-error" @if (!$errors->has('password')) hidden @endif>@error('password'){{ $message }}@enderror</span>
        </div>
        <div class="guest-field">
            <label for="reset-confirm">Ulangi kata sandi</label>
            <div class="password-field">
                <input id="reset-confirm" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Ketik sekali lagi" required aria-describedby="reset-confirm-error" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}">
                <button type="button" data-password-toggle="reset-confirm" aria-label="Lihat kata sandi" aria-pressed="false"><span data-icon="eye"></span></button>
            </div>
            <span class="field-error" id="reset-confirm-error" @if (!$errors->has('password_confirmation')) hidden @endif>@error('password_confirmation'){{ $message }}@enderror</span>
        </div>
        <button class="button button-primary auth-submit" type="submit">Simpan kata sandi<span data-icon="arrow-right"></span></button>
    </form>
    <a href="{{ route('login') }}" class="back-link"><span data-icon="arrow-left"></span>Balik ke halaman masuk</a>
</section>
@endsection
