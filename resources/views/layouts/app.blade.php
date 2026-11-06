<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#faf9f4">
    <meta name="realtime-config" content="{{ json_encode(['key' => config('broadcasting.connections.reverb.key'), 'port' => (int) (config('broadcasting.connections.reverb.options.port') ?: 8080)]) }}">
    <title>{{ $title ?? 'Ruang Belajar' }} - Ruang Belajar</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @foreach(['prototype', 'friendly', 'garden', 'workspace', 'rooms', 'app-live', 'learning-live'] as $sheet)
        <link rel="stylesheet" href="{{ asset('ui/'.$sheet.'.css') }}?v={{ filemtime(public_path('ui/'.$sheet.'.css')) }}">
    @endforeach
    <script defer src="{{ asset('ui/icons.js') }}"></script>
    <script defer src="{{ asset('ui/garden.js') }}"></script>
    @stack('styles')
</head>
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $isChat = request()->routeIs('chat.show', 'chat.show.legacy', 'chat.general', 'study-rooms.show');
    $home = route($isAdmin ? 'admin.dashboard' : 'student.dashboard');
    $navigation = $isAdmin ? [
        ['admin.dashboard', 'home', 'Beranda', request()->routeIs('admin.dashboard')],
        ['admin.users.index', 'users', 'Pengguna', request()->routeIs('admin.users.*')],
        ['admin.documents.index', 'folder', 'Dokumen', request()->routeIs('admin.documents.*')],
        ['admin.revenue', 'layers', 'Pendapatan', request()->routeIs('admin.revenue')],
    ] : [
        ['student.dashboard', 'home', 'Beranda', request()->routeIs('student.dashboard')],
        ['documents.index', 'folder', 'Materi saya', request()->routeIs('documents.*', 'folders.*', 'summaries.*', 'quizzes.*', 'flashcards.*')],
        ['chat.general', 'message-circle', 'Tanya AI', request()->routeIs('chat.general') || (request()->routeIs('chat.show', 'chat.show.legacy') && !request()->route('session')?->document_id && !request()->route('session')?->folder_id)],
        ['chat.index', 'history', 'Riwayat belajar', request()->routeIs('chat.index') || (request()->routeIs('chat.show', 'chat.show.legacy') && (request()->route('session')?->document_id || request()->route('session')?->folder_id))],
        ['study-rooms.index', 'users', 'Belajar bareng', request()->routeIs('study-rooms.*')],
    ];
@endphp
<body class="workspace-view live-app {{ $isChat ? 'live-chat' : '' }}" x-data="{ mobileNav: false }" @keydown.escape.window="mobileNav = false">
    <a class="skip-link" href="#main">Lewati navigasi</a>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navigasi utama">
            <a href="{{ $home }}" class="brand"><span class="brand-symbol" data-icon="book"></span><span>ruangbelajar</span></a>
            <p class="nav-label">{{ $isAdmin ? 'Kelola ruang belajar' : 'Tempat belajarmu' }}</p>
            <nav class="main-nav">
                @foreach($navigation as [$route, $icon, $label, $active])
                    <x-nav-link :href="route($route)" :icon="$icon" :label="$label" :active="$active" />
                @endforeach
            </nav>
            <div class="sidebar-bottom">
                @unless($isAdmin)
                    <a class="help-link" href="{{ route('student.guide') }}" @if(request()->routeIs('student.guide')) aria-current="page" @endif><span data-icon="help"></span>Cara pakainya<span data-icon="arrow-up-right"></span></a>
                @endunless
                <a class="account" href="{{ route('profile.edit') }}" aria-label="Atur akun {{ $user->name }}">
                    <span class="avatar">{{ mb_strtoupper(mb_substr($user->name ?: $user->username, 0, 1)) }}</span>
                    <div><strong>{{ $user->name ?: $user->username }}</strong><span>{{ $isAdmin ? 'Pengelola' : 'Ruang belajar pribadi' }}</span></div>
                    <span data-icon="chevron-right"></span>
                </a>
            </div>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <a href="{{ $home }}" class="mobile-brand">ruangbelajar</a>
                <span class="breadcrumb">{{ $isAdmin ? 'Pengelola' : 'Ruang belajarmu' }}<span>/</span><strong>{{ $title ?? 'Beranda' }}</strong></span>
                <div class="topbar-actions">
                    <button type="button" class="mobile-menu-button" @click="mobileNav = !mobileNav" :aria-expanded="mobileNav" aria-controls="mobile-menu" aria-label="Buka menu akun"><i data-lucide="menu" aria-hidden="true"></i></button>
                    <form class="desktop-logout" method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><i data-lucide="log-out" aria-hidden="true"></i><span>Keluar</span></button></form>
                </div>
                <nav id="mobile-menu" class="mobile-account-menu" x-show="mobileNav" x-cloak @click.outside="mobileNav = false" aria-label="Akun dan panduan">
                    <a href="{{ route('profile.edit') }}">Akun saya</a>
                    @unless($isAdmin)<a href="{{ route('student.guide') }}">Cara pakainya</a><a href="{{ route('documents.create') }}">Tambah materi</a>@endunless
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><i data-lucide="log-out" aria-hidden="true"></i><span>Keluar</span></button></form>
                </nav>
            </header>
            <main id="main" tabindex="-1">
                @if(session('status'))
                    <div class="live-alert live-alert-success" role="status" x-data="{ show: true }" x-show="show"><span data-icon="check"></span><p>{{ session('status') }}</p><button type="button" @click="show = false" aria-label="Tutup pemberitahuan"><span data-icon="close"></span></button></div>
                @endif
                @if($errors->any())
                    <div class="live-alert live-alert-error" role="alert"><span data-icon="info"></span><div><strong>Periksa lagi, ya.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
                @endif
                {{ $slot }}
                @unless($isChat)<footer class="page-footer"><span>Pelan-pelan, yang penting makin paham.</span><span>Ruang Belajar by Yasti {{ date('Y') }}</span></footer>@endunless
            </main>
        </div>
        <nav class="mobile-nav" aria-label="Navigasi utama seluler">
            @foreach($navigation as [$route, $icon, $label, $active])
                <a href="{{ route($route) }}" @class(['active' => $active]) @if($active) aria-current="page" @endif><span data-icon="{{ $icon }}"></span>{{ ['Riwayat belajar' => 'Riwayat', 'Belajar bareng' => 'Bareng', 'Materi saya' => 'Materi'][$label] ?? $label }}</a>
            @endforeach
        </nav>
    </div>
    @stack('scripts')
</body>
</html>
