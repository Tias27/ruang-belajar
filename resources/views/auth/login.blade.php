<x-app-layout title="Masuk">
    @php($brandLogo = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg'))
    <div class="mx-auto grid min-h-[calc(100vh-3rem)] max-w-6xl items-center gap-5 py-6 lg:grid-cols-[.95fr_1.05fr] lg:gap-8">
        <section class="hidden lg:block">
            <div class="max-w-lg">
                <span class="inline-flex items-center gap-2 rounded-full bg-campus-50 px-4 py-2 text-sm font-semibold text-campus-700">
                    <i data-lucide="sparkles" class="h-4 w-4"></i> RuangBelajar AI
                </span>
                <h1 class="mt-6 text-4xl font-semibold leading-tight tracking-tight text-campus-900">
                    Belajar dari dokumen jadi lebih ringan.
                </h1>
                <p class="mt-4 text-sm leading-6 text-slate-500">
                    Masuk untuk melanjutkan ringkasan, tanya AI, latihan soal, dan flashcard dari materi yang sudah kamu upload.
                </p>
                <div class="mt-8 grid gap-3">
                    @foreach([
                        ['icon' => 'folder-up', 'title' => 'Upload materi', 'text' => 'PDF, Word, dan PPT bisa dipakai sebagai bahan belajar.'],
                        ['icon' => 'messages-square', 'title' => 'Tanya AI', 'text' => 'Jawaban dibuat dari isi materi yang kamu pilih.'],
                        ['icon' => 'list-checks', 'title' => 'Latihan cepat', 'text' => 'Buat soal dan flashcard tanpa menyusun manual.'],
                    ] as $item)
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-campus-50 text-campus-700">
                                <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $item['text'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-[430px] rounded-[1.5rem] border border-slate-200 bg-white px-5 py-5 shadow-panel sm:rounded-[1.75rem] sm:px-8 sm:py-8">
            <div class="text-center">
                <img src="{{ $brandLogo }}" alt="Logo RuangBelajar AI" class="mx-auto h-14 w-14 rounded-2xl object-cover shadow-sm ring-1 ring-slate-200">
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-campus-900 sm:mt-5 sm:text-3xl">Masuk</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Lanjutkan belajar dari materi yang sudah kamu simpan.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-3 sm:mt-7 sm:space-y-4">
                @csrf
                <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Nama pengguna</span>
                    <span class="relative mt-2 block">
                        <i data-lucide="user-round" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Contoh: yasti123" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;">
                    </span>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Kata sandi</span>
                    <span class="relative mt-2 block">
                        <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input name="password" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;">
                    </span>
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input name="remember" type="checkbox"> Ingat saya
                </label>

                <button class="w-full rounded-full bg-campus-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-campus-900">
                    Masuk
                </button>

                <p class="text-center text-sm text-slate-500">
                    Belum punya akun?
                    <a class="font-semibold text-campus-700" href="{{ route('register') }}">Daftar di sini</a>
                </p>
            </form>
        </section>
    </div>
</x-app-layout>
