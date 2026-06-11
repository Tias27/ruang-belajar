<x-app-layout title="Masuk">
    <div class="mx-auto grid min-h-[calc(100vh-3rem)] max-w-6xl items-center gap-8 lg:grid-cols-[1fr_420px]">
        <section class="hidden lg:block">
            <p class="text-sm font-semibold text-campus-700">RuangBelajar AI</p>
            <h1 class="mt-3 max-w-xl text-4xl font-semibold tracking-tight text-campus-900">Masuk, lalu lanjut pahami materi dengan cara yang lebih ringan.</h1>
            <div class="mt-8 grid max-w-xl gap-3">
                @foreach([
                    ['icon' => 'file-search', 'text' => 'Tanya AI berdasarkan isi file yang kamu upload.'],
                    ['icon' => 'notebook-tabs', 'text' => 'Buat ringkasan, latihan soal, dan flashcard otomatis.'],
                    ['icon' => 'history', 'text' => 'Simpan riwayat belajar agar mudah dilanjutkan kapan saja.'],
                ] as $item)
                    <div class="flex gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-campus-50 text-campus-700"><i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i></span>
                        <p class="text-sm leading-6 text-slate-600">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="mx-auto w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-panel">
            <div>
                <p class="text-sm font-semibold text-campus-700">Masuk</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900">Selamat datang kembali</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan nama pengguna dan kata sandi akunmu.</p>
            </div>
            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <label class="block text-sm font-semibold text-slate-800">Nama pengguna
                    <input name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="" class="mt-2">
                </label>
                <label class="block text-sm font-semibold text-slate-800">Kata sandi
                    <input name="password" type="password" required autocomplete="current-password" class="mt-2">
                </label>
                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input name="remember" type="checkbox"> Ingat saya
                    </label>
                    <a class="text-sm font-semibold text-campus-700" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                </div>
                <button class="w-full rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">Masuk</button>
                <p class="text-center text-sm text-slate-500">Belum punya akun? <a class="font-semibold text-campus-700" href="{{ route('register') }}">Daftar</a></p>
            </form>
        </section>
    </div>
</x-app-layout>
