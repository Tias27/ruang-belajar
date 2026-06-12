<x-app-layout title="Kelola Pengguna">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <p class="text-sm font-semibold text-campus-700">Pengelola</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900">Kelola Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">Lihat akun pembelajar dan ubah peran bila diperlukan.</p>
    </section>
    <div class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Nama pengguna</th><th class="px-4 py-3">Surel</th><th class="px-4 py-3">Jenjang atau jurusan</th><th class="px-4 py-3">Peran</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $user->username }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->program_studi ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex gap-2">
                                @csrf @method('PATCH')
                                <select name="role">
                                    <option value="mahasiswa" @selected($user->role === 'mahasiswa')>pembelajar</option>
                                    <option value="admin" @selected($user->role === 'admin')>pengelola</option>
                                </select>
                                <button class="rounded-lg bg-campus-700 px-3 py-2 text-xs font-semibold text-white">Simpan</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Hapus</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
