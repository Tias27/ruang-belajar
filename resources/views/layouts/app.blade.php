<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'RuangBelajar AI' }}</title>
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
<body class="min-h-screen bg-[#f8fbff] text-slate-900 antialiased" x-data="{mobileNav:false}">
    @auth
        @php($isAdmin = auth()->user()->isAdmin())
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-slate-200 bg-white px-5 py-5 lg:block">
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-campus-900 text-lg font-bold text-white shadow-sm">C</span>
                <span><span class="block text-lg font-semibold tracking-tight">RuangBelajar AI</span><span class="text-xs text-slate-500">{{ $isAdmin ? 'Pusat Pengelolaan' : 'Belajar dari Materi' }}</span></span>
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
                @else
                    <x-nav-link href="{{ route('student.dashboard') }}" icon="home" label="Beranda" :active="request()->routeIs('student.dashboard')" />
                    <x-nav-link href="{{ route('documents.index') }}" icon="library" label="Materi Saya" :active="request()->routeIs('documents.index', 'documents.show', 'folders.*', 'summaries.*', 'quizzes.*', 'flashcards.*')" />
                    <x-nav-link href="{{ route('chat.index') }}" icon="messages-square" label="Riwayat AI" :active="request()->routeIs('chat.*')" />
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
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur lg:ml-64">
            <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 lg:hidden">
                    <button type="button" x-on:click="mobileNav = ! mobileNav" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <a class="font-semibold" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard') }}">{{ $isAdmin ? 'Pengelola RuangBelajar' : 'RuangBelajar AI' }}</a>
                </div>
                <div class="hidden text-sm text-slate-500 lg:block">{{ $subtitle ?? ($isAdmin ? 'Kelola sistem RuangBelajar AI' : 'Belajar dari materi dengan bantuan AI') }}</div>
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
                    @else
                        <x-nav-link href="{{ route('student.dashboard') }}" icon="home" label="Beranda" :active="request()->routeIs('student.dashboard')" />
                        <x-nav-link href="{{ route('documents.index') }}" icon="library" label="Materi Saya" :active="request()->routeIs('documents.index', 'documents.show', 'folders.*', 'summaries.*', 'quizzes.*', 'flashcards.*')" />
                        <x-nav-link href="{{ route('chat.index') }}" icon="messages-square" label="Riwayat AI" :active="request()->routeIs('chat.*')" />
                        <x-nav-link href="{{ route('documents.create') }}" icon="folder-plus" label="Upload Materi" :active="request()->routeIs('documents.create')" />
                    @endif
                    <x-nav-link href="{{ route('profile.edit') }}" icon="user-round" label="{{ $isAdmin ? 'Profil' : 'Akun Saya' }}" :active="request()->routeIs('profile.*')" />
                </nav>
            </div>
        </header>
    @endauth
    <main class="@auth lg:ml-64 @endauth">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-5 flex items-start gap-3 rounded-lg border border-campus-100 bg-campus-50 px-4 py-3 text-sm text-campus-800 shadow-sm">
                    <i data-lucide="check-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="mb-5 flex items-start gap-3 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
            {{ $slot }}
        </div>
    </main>
</body>
</html>
