<x-app-layout title="Daftar">
    <div class="mx-auto max-w-2xl py-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
            <p class="text-sm font-semibold text-campus-700">Akun Belajar</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900">Daftar RuangBelajar AI</h1>
            <p class="mt-1 text-sm leading-6 text-slate-500">Cukup isi email, nama pengguna, jenjang atau jurusan, dan kata sandi.</p>
        </section>
        <form method="POST" action="{{ route('register') }}" class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <label class="block text-sm font-semibold text-slate-800">email
                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@gmail.com" class="mt-2">
            </label>
            <label class="block text-sm font-semibold text-slate-800">Nama pengguna
                <input name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Contoh: tias, tias 123, atau tias.rpl" class="mt-2">
                <span class="mt-1 block text-xs text-slate-500">Boleh pakai spasi, angka, titik, garis bawah, atau tanda hubung.</span>
            </label>
            <label class="block text-sm font-semibold text-slate-800">Jenjang atau jurusan
                <input name="program_studi" value="{{ old('program_studi') }}" required placeholder="Contoh: SMP kelas 8, SMK RPL, atau Teknik Informatika" class="mt-2">
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold text-slate-800">Kata sandi
                    <input name="password" type="password" required autocomplete="new-password" class="mt-2">
                </label>
                <label class="block text-sm font-semibold text-slate-800">Konfirmasi kata sandi
                    <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2">
                </label>
            </div>
            <button class="rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">Buat Akun</button>
            <p class="text-center text-sm text-slate-500">Sudah punya akun? <a class="font-semibold text-campus-700" href="{{ route('login') }}">Masuk</a></p>
        </form>
    </div>
</x-app-layout>
