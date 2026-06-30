<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ruang Belajar' }}</title>
    @php
        $brandLogo = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg');
    @endphp
    <link rel="icon" href="{{ $brandLogo }}">
    <link rel="apple-touch-icon" href="{{ $brandLogo }}">
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            campus: {50:'#f3f9ff',100:'#e1f1ff',300:'#91cdf8',500:'#2f8ee8',600:'#1f73c7',700:'#1456a3',900:'#0b1f44'},
                            accent: {50:'#f5f2ff',100:'#e9e2ff',500:'#7c5cff',700:'#5d3ed8'}
                        },
                        boxShadow: {
                            panel: '0 1px 2px rgba(15, 23, 42, .05), 0 14px 30px rgba(15, 23, 42, .06)'
                        }
                    }
                }
            };
        </script>
    @endif
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());
        document.addEventListener('click', () => setTimeout(() => window.lucide?.createIcons(), 0));
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    <!-- Pusher & Echo CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>
    <script>
        try {
            if (window.Pusher && window.Echo) {
                const EchoClient = window.Echo;
                const isHttpsProtocol = window.location.protocol === 'https:';

                window.Echo = new EchoClient({
                    broadcaster: 'reverb',
                    key: '{{ config('broadcasting.connections.reverb.key') }}',
                    wsHost: window.location.hostname,
                    wsPort: isHttpsProtocol ? 443 : {{ config('broadcasting.connections.reverb.options.port') ?? 8080 }},
                    wssPort: isHttpsProtocol ? 443 : {{ config('broadcasting.connections.reverb.options.port') ?? 8080 }},
                    forceTLS: isHttpsProtocol,
                    enabledTransports: ['ws', 'wss'],
                });
            }
        } catch (error) {
            window.Echo = null;
            console.warn('Realtime study room tidak aktif, memakai polling.', error);
        }
    </script>

    <style>
        [x-cloak]{display:none!important}
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
        select,
        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: .5rem;
            background: #fff;
            color: #0f172a;
            font-size: .875rem;
            line-height: 1.25rem;
            padding: .85rem 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        textarea {
            line-height: 1.6;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):focus,
        select:focus,
        textarea:focus {
            border-color: #2f8ee8;
            box-shadow: 0 0 0 4px rgba(47, 142, 232, .14);
        }
        input::placeholder,
        textarea::placeholder {
            color: #94a3b8;
        }
        input[type="checkbox"],
        input[type="radio"] {
            width: 1rem;
            height: 1rem;
            border: 1px solid #cbd5e1;
            accent-color: #1456a3;
        }
        input[type="file"] {
            max-width: 100%;
        }
    </style>
</head>
<body class="bg-[#f8fbff] text-slate-900 antialiased overflow-x-hidden flex flex-col {{ request()->routeIs('chat.show', 'chat.show.legacy', 'study-rooms.show') ? 'h-screen h-[100dvh] overflow-hidden' : 'min-h-screen' }}" x-data="{mobileNav:false}">
    @auth
        @php
            $isAdmin = auth()->user()->isAdmin();
        @endphp
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-slate-200 bg-white px-5 py-5 lg:block">
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ $brandLogo }}" alt="Logo Ruang Belajar" class="h-11 w-11 rounded-2xl object-cover shadow-sm ring-1 ring-slate-200">
                <span><span class="block text-lg font-semibold tracking-tight">Ruang Belajar</span><span class="text-xs text-slate-500">{{ $isAdmin ? 'Pusat Pengelolaan' : 'Belajar dari Materi' }}</span></span>
            </a>
            <div class="mt-6 rounded-lg border {{ $isAdmin ? 'border-campus-100 bg-campus-50 text-campus-900' : 'border-accent-100 bg-accent-50 text-accent-700' }} p-3 text-xs leading-5">
                <span class="font-semibold">{{ $isAdmin ? 'Panel Sistem' : 'Mulai dari sini' }}</span>
                <span class="mt-1 block {{ $isAdmin ? 'text-campus-700' : 'text-slate-600' }}">{{ $isAdmin ? 'Pantau aktivitas dan kelola data sistem.' : 'Upload materi, pahami, lalu latihan.' }}</span>
            </div>
            <nav class="mt-6 space-y-1 text-sm font-medium">
                @if($isAdmin)
                    <x-nav-link href="{{ route('admin.dashboard') }}" icon="layout-dashboard" label="Beranda Pengelola" :active="request()->routeIs('admin.dashboard')" />
                    <x-nav-link href="{{ route('admin.users.index') }}" icon="users" label="Pengguna" :active="request()->routeIs('admin.users.*')" />
                    <x-nav-link href="{{ route('admin.documents.index') }}" icon="files" label="Dokumen" :active="request()->routeIs('admin.documents.*')" />
                    <x-nav-link href="{{ route('admin.analysis') }}" icon="bar-chart-2" label="Analisa" :active="request()->routeIs('admin.analysis')" />
                @else
                    <x-nav-link href="{{ route('student.dashboard') }}" icon="home" label="Beranda" :active="request()->routeIs('student.dashboard')" />
                    <x-nav-link href="{{ route('student.guide') }}" icon="map" label="Panduan" :active="request()->routeIs('student.guide')" />
                    <x-nav-link href="{{ route('documents.index') }}" icon="library" label="Materi Saya" :active="request()->routeIs('documents.index', 'documents.show', 'folders.*', 'summaries.*', 'quizzes.*', 'flashcards.*')" />
                    <x-nav-link href="{{ route('chat.index') }}" icon="messages-square" label="Riwayat AI" :active="request()->routeIs('chat.*')" />
                    <x-nav-link href="{{ route('study-rooms.index') }}" icon="users" label="Belajar Bareng" :active="request()->routeIs('study-rooms.index', 'study-rooms.show')" />
                    <x-nav-link href="{{ route('documents.create') }}" icon="folder-plus" label="Upload Materi" :active="request()->routeIs('documents.create')" />
                @endif
                <x-nav-link href="{{ route('profile.edit') }}" icon="user-round" label="{{ $isAdmin ? 'Profil' : 'Akun Saya' }}" :active="request()->routeIs('profile.*')" />
            </nav>
            @unless($isAdmin)
                <a href="{{ route('documents.create') }}" class="absolute inset-x-5 bottom-5 flex items-center gap-3 rounded-lg border border-campus-100 bg-campus-50 p-3 text-sm font-semibold text-campus-900 transition hover:bg-campus-100">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white text-campus-700 shadow-sm"><i data-lucide="sparkles" class="h-4 w-4"></i></span>
                    <span class="min-w-0"><span class="block">Upload materi</span><span class="block truncate text-xs font-medium text-slate-500">PDF, Word, atau PPT</span></span>
                </a>
            @endunless
        </aside>
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white lg:ml-64">
            <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 lg:hidden">
                    <button type="button" x-on:click="mobileNav = ! mobileNav" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <a class="flex min-w-0 items-center gap-2 font-semibold" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}">
                        <img src="{{ $brandLogo }}" alt="Logo" class="h-8 w-8 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                        <span class="min-w-0 truncate">{{ $isAdmin ? 'Pengelola Ruang Belajar' : 'Ruang Belajar' }}</span>
                    </a>
                </div>
                <div class="hidden text-sm text-slate-500 lg:block">{{ $subtitle ?? ($isAdmin ? 'Kelola sistem Ruang Belajar' : 'Belajar dari materi dengan bantuan AI') }}</div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <span class="block text-sm font-semibold text-slate-800">{{ auth()->user()->username }}</span>
                        <span class="block text-xs capitalize text-slate-500">{{ $isAdmin ? 'pengelola' : 'pembelajar' }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-100">
                            <i data-lucide="log-out" class="h-4 w-4"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
            <div x-show="mobileNav" x-cloak class="border-t border-slate-200 bg-white px-4 py-3 lg:hidden">
                <nav class="space-y-1 text-sm font-medium">
                    @if($isAdmin)
                        <x-nav-link href="{{ route('admin.dashboard') }}" icon="layout-dashboard" label="Beranda Pengelola" :active="request()->routeIs('admin.dashboard')" />
                        <x-nav-link href="{{ route('admin.users.index') }}" icon="users" label="Pengguna" :active="request()->routeIs('admin.users.*')" />
                        <x-nav-link href="{{ route('admin.documents.index') }}" icon="files" label="Dokumen" :active="request()->routeIs('admin.documents.*')" />
                        <x-nav-link href="{{ route('admin.analysis') }}" icon="bar-chart-2" label="Analisa" :active="request()->routeIs('admin.analysis')" />
                    @else
                        <x-nav-link href="{{ route('student.dashboard') }}" icon="home" label="Beranda" :active="request()->routeIs('student.dashboard')" />
                        <x-nav-link href="{{ route('student.guide') }}" icon="map" label="Panduan" :active="request()->routeIs('student.guide')" />
                        <x-nav-link href="{{ route('documents.index') }}" icon="library" label="Materi Saya" :active="request()->routeIs('documents.index', 'documents.show', 'folders.*', 'summaries.*', 'quizzes.*', 'flashcards.*')" />
                        <x-nav-link href="{{ route('chat.index') }}" icon="messages-square" label="Riwayat AI" :active="request()->routeIs('chat.*')" />
                        <x-nav-link href="{{ route('study-rooms.index') }}" icon="users" label="Belajar Bareng" :active="request()->routeIs('study-rooms.index', 'study-rooms.show')" />
                        <x-nav-link href="{{ route('documents.create') }}" icon="folder-plus" label="Upload Materi" :active="request()->routeIs('documents.create')" />
                    @endif
                    <x-nav-link href="{{ route('profile.edit') }}" icon="user-round" label="{{ $isAdmin ? 'Profil' : 'Akun Saya' }}" :active="request()->routeIs('profile.*')" />
                </nav>
            </div>
        </header>
    @endauth
    <main class="@auth lg:ml-64 @endauth flex flex-col flex-1 {{ request()->routeIs('chat.show', 'chat.show.legacy', 'study-rooms.show') ? 'min-h-0 overflow-hidden' : '' }}">
        <div class="{{ request()->routeIs('chat.show', 'chat.show.legacy', 'study-rooms.show') ? 'flex flex-col flex-1 h-full min-h-0 overflow-hidden' : 'mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 w-full' }}">
            @if(session('status'))
                <div x-data="{ show: true }"
                     x-show="show"
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="mb-5 flex items-start gap-3 rounded-[1.25rem] border border-emerald-100 bg-emerald-50 px-4 py-4 text-sm text-emerald-900 shadow-sm sm:px-5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-emerald-700 shadow-sm">
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="block font-semibold">Berhasil</span>
                                <span class="mt-0.5 block leading-6">{{ session('status') }}</span>
                            </div>
                            <button type="button" @click="show = false" class="text-emerald-700/60 hover:text-emerald-900 transition ml-2">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </span>
                </div>
            @endif
            @if($errors->any())
                <div x-data="{ show: true }"
                     x-show="show"
                     x-init="setTimeout(() => show = false, 7000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="mb-5 flex items-start gap-3 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <span class="min-w-0 flex-1">{{ $errors->first() }}</span>
                    <button type="button" @click="show = false" class="text-rose-700/60 hover:text-rose-900 transition ml-2">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            @endif
            {{ $slot }}
            @unless(request()->routeIs('chat.show', 'chat.show.legacy', 'study-rooms.show'))
            <footer class="mt-8 pb-2 text-center text-xs text-slate-400">
                <span>Ruang Belajar by Yasti 2026</span>
            </footer>
            @endunless
        </div>
    </main>
    @auth
        @unless(auth()->user()->isAdmin())
            @php
                $guideMaterialCount = auth()->user()->documents()->count() + auth()->user()->documentFolders()->count();
                $guideOnCreate = request()->routeIs('documents.create');
                $guideOnGuide = request()->routeIs('student.guide');
                $guideOnMaterial = request()->routeIs('documents.show', 'folders.show');
                $guideOnChat = request()->routeIs('chat.show', 'chat.show.legacy', 'study-rooms.show');
            @endphp
            @unless($guideOnGuide || $guideOnChat)
                <div
                    x-data="{ open: {{ $guideMaterialCount === 0 && ! $guideOnCreate ? 'true' : 'false' }} }"
                    class="fixed bottom-4 right-4 z-50 w-[calc(100%-2rem)] max-w-sm"
                >
                    <button
                        type="button"
                        x-show="! open"
                        x-on:click="open = true"
                        class="ml-auto flex h-12 items-center gap-2 rounded-full bg-campus-700 px-4 text-sm font-semibold text-white shadow-panel hover:bg-campus-900"
                    >
                        <i data-lucide="help-circle" class="h-4 w-4"></i>
                        Butuh arahan?
                    </button>

                    <div x-show="open" x-cloak class="overflow-hidden rounded-[1.35rem] border border-campus-100 bg-white shadow-panel">
                        <div class="flex items-start gap-3 bg-campus-50 p-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-white text-campus-700 shadow-sm">
                                <i data-lucide="{{ $guideMaterialCount === 0 ? 'file-up-2' : ($guideOnMaterial ? 'sparkles' : 'map') }}" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-campus-900">
                                    @if($guideMaterialCount === 0)
                                        Mulai dari upload materi
                                    @elseif($guideOnMaterial)
                                        Pilih fitur sesuai kebutuhan
                                    @else
                                        Bingung mulai dari mana?
                                    @endif
                                </p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    @if($guideMaterialCount === 0)
                                        AI baru bisa dipakai setelah kamu upload PDF, Word, atau PPT.
                                    @elseif($guideOnMaterial)
                                        Ringkas untuk gambaran besar, Tanya materi untuk bagian sulit, Latihan untuk tes, Flashcard untuk mengulang.
                                    @else
                                        Urutan paling aman: upload materi, buka file/folder, lalu pilih fitur AI.
                                    @endif
                                </p>
                            </div>
                            <button type="button" x-on:click="open = false" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-slate-500 hover:bg-white">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div class="grid gap-2 p-4 sm:grid-cols-2">
                            @if($guideMaterialCount === 0)
                                <a href="{{ route('documents.create') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-campus-700 px-4 text-sm font-semibold text-white hover:bg-campus-900">
                                    <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload
                                </a>
                                <a href="{{ route('student.guide') }}" class="inline-flex h-10 items-center justify-center rounded-full bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Lihat panduan</a>
                            @elseif($guideOnMaterial)
                                <a href="{{ route('student.guide') }}" class="inline-flex h-10 items-center justify-center rounded-full bg-campus-700 px-4 text-sm font-semibold text-white hover:bg-campus-900">Lihat fungsi fitur</a>
                                <button type="button" x-on:click="open = false" class="inline-flex h-10 items-center justify-center rounded-full bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Mengerti</button>
                            @else
                                <a href="{{ route('documents.index') }}" class="inline-flex h-10 items-center justify-center rounded-full bg-campus-700 px-4 text-sm font-semibold text-white hover:bg-campus-900">Pilih materi</a>
                                <a href="{{ route('student.guide') }}" class="inline-flex h-10 items-center justify-center rounded-full bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">Panduan</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endunless
        @endunless
    @endauth
</body>
</html>
