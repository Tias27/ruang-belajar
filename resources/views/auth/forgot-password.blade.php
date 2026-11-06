@extends('layouts.guest')

@section('title', 'Lupa kata sandi - Ruang Belajar')

@section('content')
<section class="screen forgot-screen" id="screen-forgot" aria-labelledby="forgot-title">
    <div class="forgot-icon" data-icon="mail"></div>
    <p class="eyebrow">Nggak apa-apa, bisa diatur ulang.</p>
    <h1 id="forgot-title">Lupa kata sandi?</h1>
    <p class="subtitle">Masukkan email yang kamu pakai saat daftar.</p>
    @include('auth._feedback')
    <form id="forgot-form" method="POST" action="{{ route('password.email') }}" class="guest-form">
        @csrf
        <div class="guest-field">
            <label for="forgot-email">Email akunmu</label>
            <input id="forgot-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required aria-describedby="forgot-email-error" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
            <span class="field-error" id="forgot-email-error" @if (!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</span>
        </div>
        <button class="button button-primary auth-submit" type="submit">Kirim tautan reset<span data-icon="arrow-right"></span></button>
    </form>
    <a href="{{ route('login') }}" class="back-link"><span data-icon="arrow-left"></span>Balik ke halaman masuk</a>
</section>
@endsection
