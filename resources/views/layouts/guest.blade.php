<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f6f2">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Belajar')</title>
    @foreach(['prototype', 'friendly', 'guest', 'garden', 'guest-live'] as $sheet)
        <link rel="stylesheet" href="{{ asset('ui/'.$sheet.'.css') }}?v={{ filemtime(public_path('ui/'.$sheet.'.css')) }}">
    @endforeach
    <script src="{{ asset('ui/icons.js') }}" defer></script>
    <script src="{{ asset('ui/guest-live.js') }}" defer></script>
    <script src="{{ asset('ui/garden.js') }}" defer></script>
    @stack('styles')
</head>
<body class="guest-view @yield('body-class', 'auth-view')">
    <a class="skip-link" href="#main">Lewati navigasi</a>
    <header class="guest-header" id="guest-header">
        <a href="{{ url('/') }}" class="guest-brand" aria-label="Ruang Belajar, halaman awal"><span data-icon="book"></span>ruangbelajar</a>
        <nav class="guest-links" aria-label="Tentang Ruang Belajar">
            <a href="{{ url('/') }}#how">Cara kerjanya</a>
            <a href="{{ url('/') }}#features">Bisa bantu apa?</a>
        </nav>
        <div class="guest-actions">
            <a href="{{ route('login') }}" class="guest-login-link">Masuk</a>
            <a href="{{ route('register') }}" class="button button-primary">Mulai belajar<span data-icon="arrow-right"></span></a>
        </div>
        <a href="{{ url('/') }}" class="auth-back-link"><span data-icon="arrow-left"></span>Kembali ke awal</a>
    </header>
    <div class="app-shell">
        <div class="workspace">
            <main id="main" tabindex="-1">@yield('content')</main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
