<x-app-layout>
    <div class="mx-auto max-w-md py-10">
        <h1 class="text-3xl font-semibold text-campus-900">Atur ulang kata sandi</h1>
        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="block text-sm font-medium">Surel
                <input name="email" type="email" value="{{ old('email', $request->email) }}" required class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="block text-sm font-medium">Kata sandi baru
                <input name="password" type="password" required class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <label class="block text-sm font-medium">Konfirmasi kata sandi
                <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border-slate-300">
            </label>
            <button class="w-full rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white hover:bg-campus-900">Simpan kata sandi</button>
        </form>
    </div>
</x-app-layout>
