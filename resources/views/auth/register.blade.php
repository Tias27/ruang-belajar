<x-app-layout title="Daftar">
    @php($brandLogo = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg'))
    <div class="mx-auto grid min-h-[calc(100vh-3rem)] max-w-6xl items-center gap-5 py-6 lg:grid-cols-[.95fr_1.05fr] lg:gap-8">
        <section class="hidden lg:block">
            <div class="max-w-lg">
                <span class="inline-flex items-center gap-2 rounded-full bg-campus-50 px-4 py-2 text-sm font-semibold text-campus-700">
                    <i data-lucide="book-open-check" class="h-4 w-4"></i> Mulai belajar
                </span>
                <h1 class="mt-6 text-4xl font-semibold leading-tight tracking-tight text-campus-900">
                    Buat akun, upload materi, lalu belajar dengan AI.
                </h1>
                <p class="mt-4 text-sm leading-6 text-slate-500">
                    Simpan semua materi, ringkasan, latihan, flashcard, dan riwayat tanya AI dalam satu akun.
                </p>
                <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <span class="text-sm font-semibold text-slate-900">Alur belajar</span>
                        <span class="rounded-full bg-campus-50 px-3 py-1 text-xs font-semibold text-campus-700">3 langkah</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach([
                            ['no' => '1', 'text' => 'Upload file atau folder materi.'],
                            ['no' => '2', 'text' => 'Pilih ringkas, tanya AI, latihan, atau flashcard.'],
                            ['no' => '3', 'text' => 'Buka ulang hasil belajar kapan saja.'],
                        ] as $step)
                            <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-campus-500 text-xs font-semibold text-white">{{ $step['no'] }}</span>
                                <p class="text-sm text-slate-600">{{ $step['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <form
            method="POST"
            action="{{ route('register') }}"
            class="mx-auto w-full max-w-[470px] rounded-[1.5rem] border border-slate-200 bg-white px-5 py-5 shadow-panel sm:rounded-[1.75rem] sm:px-8 sm:py-8"
            x-data="{
                password: '',
                confirmation: '',
                get hasConfirmation() {
                    return this.confirmation.length > 0;
                },
                get isLongEnough() {
                    return this.password.length >= 8;
                },
                get matches() {
                    return this.password.length > 0 && this.password === this.confirmation;
                },
                get canSubmit() {
                    return this.isLongEnough && this.matches;
                }
            }"
        >
            @csrf
            <div class="text-center">
                <img src="{{ $brandLogo }}" alt="Logo RuangBelajar AI" class="mx-auto h-14 w-14 rounded-2xl object-cover shadow-sm ring-1 ring-slate-200">
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-campus-900 sm:mt-5 sm:text-3xl">Buat Akun</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Isi data singkat untuk mulai belajar dari materi kamu.</p>
            </div>

            <div class="mt-5 grid gap-3 sm:mt-7 sm:gap-4">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Email</span>
                    <span class="relative mt-2 block">
                        <i data-lucide="mail" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@gmail.com" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;">
                    </span>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Nama pengguna</span>
                    <span class="relative mt-2 block">
                        <i data-lucide="user-round" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Contoh: yasti123" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;">
                    </span>
                    <span class="mt-1 block text-xs text-slate-500">Boleh pakai spasi, angka, titik, _, atau -.</span>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-800">Jenjang atau jurusan</span>
                    <span class="relative mt-2 block">
                        <i data-lucide="school" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input name="program_studi" value="{{ old('program_studi') }}" required placeholder="Contoh: SMK RPL" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;">
                    </span>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">Kata sandi</span>
                        <span class="relative mt-2 block">
                            <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input name="password" type="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;" x-model="password" x-bind:class="password.length > 0 && ! isLongEnough ? 'border-amber-300 focus:border-amber-400 focus:ring-amber-100' : ''">
                        </span>
                        <span class="mt-1 block text-xs" x-bind:class="isLongEnough ? 'text-campus-900' : 'text-slate-500'">Minimal 8 karakter.</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">Konfirmasi</span>
                        <span class="relative mt-2 block">
                            <i data-lucide="shield-check" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Ulangi sandi" class="rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;" x-model="confirmation" x-bind:class="hasConfirmation ? (matches ? 'border-campus-300 focus:border-campus-500 focus:ring-campus-100' : 'border-rose-300 focus:border-rose-500 focus:ring-rose-100') : ''">
                        </span>
                        <span class="mt-1 block text-xs text-slate-500" x-show="! hasConfirmation">Ulangi kata sandi yang sama.</span>
                        <span class="mt-1 block text-xs text-campus-900" x-show="hasConfirmation && matches" x-cloak>Kata sandi sudah cocok.</span>
                        <span class="mt-1 block text-xs text-rose-600" x-show="hasConfirmation && ! matches" x-cloak>Belum sama.</span>
                    </label>
                </div>
            </div>

            <button x-bind:disabled="! canSubmit" class="mt-5 w-full rounded-full bg-campus-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-campus-900 disabled:cursor-not-allowed disabled:bg-slate-300 sm:mt-6">
                Buat Akun
            </button>
            <p class="mt-4 text-center text-sm text-slate-500">
                Sudah punya akun?
                <a class="font-semibold text-campus-700" href="{{ route('login') }}">Masuk di sini</a>
            </p>
        </form>
    </div>
</x-app-layout>
