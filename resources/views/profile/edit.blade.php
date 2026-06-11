<x-app-layout title="Akun Saya">
    <div class="mx-auto max-w-3xl">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
            <p class="text-sm font-semibold text-campus-700">Akun</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900">Akun Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola profil dan keamanan akun RuangBelajar AI.</p>
        </section>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <form method="POST" action="{{ route('profile.update') }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                @csrf @method('PATCH')
                <div>
                    <h2 class="font-semibold text-slate-900">Profil</h2>
                    <p class="mt-1 text-sm text-slate-500">Data ini dipakai untuk identitas akun.</p>
                </div>
                <label class="block text-sm font-semibold text-slate-800">Nama pengguna
                    <input name="username" value="{{ old('username', $user->username) }}" required class="mt-2">
                    <span class="mt-1 block text-xs text-slate-500">Boleh pakai spasi, angka, titik, garis bawah, atau tanda hubung.</span>
                </label>
                <label class="block text-sm font-semibold text-slate-800">Jenjang atau jurusan
                    <input name="program_studi" value="{{ old('program_studi', $user->program_studi) }}" class="mt-2">
                </label>
                <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $user->email }} | {{ $user->role === 'admin' ? 'pengelola' : 'pembelajar' }}</div>
                <button class="rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">Simpan profil</button>
            </form>

            <form method="POST" action="{{ route('profile.password.update') }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                @csrf @method('PATCH')
                <div>
                    <h2 class="font-semibold text-slate-900">Ganti Kata Sandi</h2>
                    <p class="mt-1 text-sm text-slate-500">Masukkan kata sandi lama sebelum membuat yang baru.</p>
                </div>
                <label class="block text-sm font-semibold text-slate-800">Kata sandi saat ini
                    <input name="current_password" type="password" required autocomplete="current-password" class="mt-2">
                </label>
                <label class="block text-sm font-semibold text-slate-800">Kata sandi baru
                    <input name="password" type="password" required autocomplete="new-password" class="mt-2">
                </label>
                <label class="block text-sm font-semibold text-slate-800">Ulangi kata sandi baru
                    <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2">
                </label>
                <button class="rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">Perbarui kata sandi</button>
            </form>
        </div>
    </div>
</x-app-layout>
