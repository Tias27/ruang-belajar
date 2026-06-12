<x-app-layout title="Akun Saya">
    <div class="mx-auto max-w-5xl min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <div class="flex min-w-0 flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <span class="inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-campus-700 shadow-sm">Akun Saya</span>
                    <h1 class="mt-4 text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">Atur profil dan keamanan</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Ubah identitas akun tanpa perlu mengganti kata sandi. Kata sandi punya form terpisah.</p>
                </div>
                <div class="flex min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-3 shadow-sm">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                        <i data-lucide="user-round" class="h-5 w-5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $user->username }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ $user->email }}</span>
                    </span>
                </div>
            </div>
        </section>

        <div class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <form method="POST" action="{{ route('profile.update') }}" class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="flex min-w-0 items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                        <i data-lucide="id-card" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900">Identitas akun</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Data ini dipakai untuk nama tampilan dan kategori belajar kamu.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <label class="block text-sm font-semibold text-slate-800">
                        Nama pengguna
                        <input name="username" value="{{ old('username', $user->username) }}" required autocomplete="username" placeholder="Contoh: tias 123" class="mt-2 rounded-full bg-slate-50">
                        <span class="mt-2 block text-xs leading-5 text-slate-500">Boleh pakai huruf, angka, spasi, titik, garis bawah, atau tanda hubung.</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-800">
                        Jenjang atau jurusan
                        <input name="program_studi" value="{{ old('program_studi', $user->program_studi) }}" placeholder="Contoh: SMK RPL / Informatika / Kelas 9" class="mt-2 rounded-full bg-slate-50">
                    </label>

                    <div class="grid gap-2 rounded-[1.1rem] bg-slate-50 p-3 text-sm text-slate-600">
                        <div class="flex min-w-0 items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase text-slate-400">Email</span>
                            <span class="min-w-0 truncate font-medium text-slate-700">{{ $user->email }}</span>
                        </div>
                        <div class="flex min-w-0 items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase text-slate-400">Role</span>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-campus-700">{{ $user->role === 'admin' ? 'Pengelola' : 'Pembelajar' }}</span>
                        </div>
                    </div>
                </div>

                <button class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-campus-700 px-5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                    <i data-lucide="save" class="h-4 w-4"></i> Simpan profil
                </button>
            </form>

            <form
                method="POST"
                action="{{ route('profile.password.update') }}"
                class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm"
                x-data="{
                    password: '',
                    confirmation: '',
                    get hasPassword() { return this.password.length > 0 },
                    get hasConfirmation() { return this.confirmation.length > 0 },
                    get isLongEnough() { return this.password.length >= 8 },
                    get matches() { return this.password.length > 0 && this.password === this.confirmation },
                }"
            >
                @csrf
                @method('PATCH')

                <div class="flex min-w-0 items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700">
                        <i data-lucide="lock-keyhole" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900">Ganti kata sandi</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Isi bagian ini hanya kalau kamu memang mau mengganti kata sandi.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <label class="block text-sm font-semibold text-slate-800">
                        Kata sandi saat ini
                        <input name="current_password" type="password" required autocomplete="current-password" placeholder="Masukkan sandi lama" class="mt-2 rounded-full bg-slate-50">
                    </label>

                    <label class="block text-sm font-semibold text-slate-800">
                        Kata sandi baru
                        <input name="password" type="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="mt-2 rounded-full bg-slate-50" x-model="password" x-bind:class="hasPassword && ! isLongEnough ? 'border-amber-300 focus:border-amber-400 focus:ring-amber-100' : ''">
                        <span x-show="hasPassword && ! isLongEnough" x-cloak class="mt-2 block text-xs font-medium text-amber-700">Minimal 8 karakter.</span>
                    </label>

                    <label class="block text-sm font-semibold text-slate-800">
                        Ulangi kata sandi baru
                        <input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Ulangi sandi baru" class="mt-2 rounded-full bg-slate-50" x-model="confirmation" x-bind:class="hasConfirmation ? (matches ? 'border-campus-300 focus:border-campus-500 focus:ring-campus-100' : 'border-rose-300 focus:border-rose-500 focus:ring-rose-100') : ''">
                        <span x-show="hasConfirmation && matches" x-cloak class="mt-2 block text-xs font-medium text-campus-700">Konfirmasi cocok.</span>
                        <span x-show="hasConfirmation && ! matches" x-cloak class="mt-2 block text-xs font-medium text-rose-700">Konfirmasi belum sama.</span>
                    </label>
                </div>

                <button x-bind:disabled="! isLongEnough || ! matches" class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-slate-900 px-5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900 disabled:cursor-not-allowed disabled:bg-slate-300">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> Perbarui kata sandi
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
